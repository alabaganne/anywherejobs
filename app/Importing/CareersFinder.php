<?php

namespace App\Importing;

use App\Models\Company;
use App\Models\ImportSource;
use Illuminate\Support\Str;
use Throwable;

// Finds where a checked company lists its jobs, starting from its own website: the employer ATS board its careers
// page links to, job pages with JobPosting data on its own site, or, when neither is there, its careers page itself.
class CareersFinder
{
    // A link to one job on the company's own site.
    private const JOB_PATH = '~/(?:jobs?|careers?|positions?|openings?|opportunities|vacanc\w*|stellen\w*|offres?|emplois?|postes?)/[^/?#]+~i';
    // A link to the careers page itself.
    private const CAREERS_PATH = '~/(?:careers?|jobs|join(?:-us)?|work-with-us|open-positions|karriere|carrieres?|emplois?|recrutement)/?$~i';

    // Returns how to read the company's jobs (provider, url, allowed_domains, settings, linked_from, jobs) or ['error' => why, 'retry' => bool].
    public function find(Company $company): array
    {
        $site = $company->website ?: ($company->domain ? 'https://'.$company->domain : null);
        $host = $site ? preg_replace('/^www\./', '', strtolower((string) parse_url($site, PHP_URL_HOST))) : null;
        if (! $host) { return ['error' => 'The company has no website.']; }
        $name = (string) $company->display_name;
        $careers = (string) $company->careers_url;
        // The careers link may point straight at the ATS board.
        if ($careers !== '' && ($board = JobKey::board($careers))) {
            $found = $this->board($board['provider'], $board['slug'], $name);
            if (! isset($found['error'])) { return $found + ['linked_from' => $careers]; }
        }

        $domains = array_values(array_unique([$host, 'www.'.$host, ...($careers !== '' && $this->onSite($careers, $host) ? [strtolower((string) parse_url($careers, PHP_URL_HOST))] : [])]));
        $http = new SourceHttp(new ImportSource(['allowed_domains' => $domains, 'request_delay_ms' => 300, 'max_requests' => 12]), microtime(true) + 90);
        $reader = new SourceReader($http);
        $queue = array_values(array_unique(array_filter([$careers !== '' && $this->onSite($careers, $host) ? $careers : null, $site])));
        $seen = []; $opened = 0; $error = 'No job list was found on the company website.';
        while ($queue && count($seen) < 4) {
            $page = array_shift($queue);
            if (isset($seen[$page])) { continue; }
            $seen[$page] = true;
            try {
                $html = $http->get($page);
                $opened++;
            } catch (Throwable $e) {
                $error = 'The careers page could not be opened ('.Str::limit($e->getMessage(), 100).').';
                continue;
            }
            $links = $this->links($html, $page);
            foreach ([...$links, ...$this->rawLinks($html)] as $link) {
                if (! ($board = JobKey::board($link))) { continue; }
                $found = $this->board($board['provider'], $board['slug'], $name);
                if (isset($found['error'])) { $error = $found['error']; continue; }
                return $found + ['linked_from' => $page];
            }
            $jobs = array_values(array_unique(array_filter($links, fn ($link) => $this->onSite($link, $host) && preg_match(self::JOB_PATH, (string) parse_url($link, PHP_URL_PATH)) && $link !== $page)));
            $structured = [];
            foreach (array_slice($jobs, 0, 3) as $job) {
                try {
                    if ($reader->jobPosting($http->get($job))) { $structured[] = $job; }
                } catch (Throwable) {
                    // A job page that can't be opened just doesn't count.
                }
            }
            if ($structured) {
                return ['provider' => 'jsonld', 'url' => $page, 'allowed_domains' => $domains, 'linked_from' => $page, 'jobs' => count($jobs),
                    'settings' => ['scope' => 'reachable', 'authoritative_employer' => true, 'employer_name' => $name, 'job_path_prefix' => $this->prefix($structured)]];
            }
            if ($jobs && (preg_match(self::CAREERS_PATH, (string) parse_url($page, PHP_URL_PATH)) || $page === $careers)) {
                return ['provider' => 'careers', 'url' => $page, 'allowed_domains' => $domains, 'linked_from' => $page, 'jobs' => count($jobs),
                    'settings' => ['scope' => 'reachable', 'authoritative_employer' => true, 'employer_name' => $name]];
            }
            foreach ($links as $link) {
                if ($this->onSite($link, $host) && preg_match(self::CAREERS_PATH, (string) parse_url($link, PHP_URL_PATH))) { $queue[] = $link; }
            }
        }
        // Nothing could be opened, so the site may only be down for now.
        return ['error' => $error, 'retry' => $opened === 0];
    }

    // Checks an ATS board: it must answer with a job list that has open jobs.
    public function board(string $provider, string $slug, string $employer): array
    {
        $url = JobKey::boardUrl($provider, $slug);
        if (! $url) { return ['error' => 'Unknown job board.']; }
        $host = (string) parse_url($url, PHP_URL_HOST);
        $probe = new ImportSource(['allowed_domains' => [$host], 'request_delay_ms' => 0, 'max_requests' => 4]);
        try {
            $data = json_decode((new SourceHttp($probe, microtime(true) + 30))->get($url), true);
        } catch (Throwable $e) {
            return ['error' => 'The job board could not be read ('.Str::limit($e->getMessage(), 100).').'];
        }
        $records = match ($provider) { 'lever' => $data, 'recruitee' => $data['offers'] ?? null, default => $data['jobs'] ?? null };
        if (! is_array($records)) { return ['error' => 'The job board did not answer with a job list.']; }
        if (! $records) { return ['error' => 'The job board has no open jobs right now.']; }
        $links = array_filter(array_map(fn ($job) => parse_url($job['absolute_url'] ?? $job['hostedUrl'] ?? $job['jobUrl'] ?? $job['careers_url'] ?? $job['url'] ?? '', PHP_URL_HOST), $records));
        return ['provider' => $provider, 'url' => $url, 'jobs' => count($records),
            'allowed_domains' => array_slice(array_values(array_unique([...JobKey::boardDomains($provider, $slug), ...$links])), 0, 20),
            'settings' => ['scope' => 'reachable', 'authoritative_employer' => true] + ($provider === 'greenhouse' ? [] : ['employer_name' => $employer])];
    }

    // Saves what find() returned as an enabled source of that company. The company's own site links to it, so it needs no approval.
    public function connect(Company $company, array $found, string $note): ImportSource
    {
        $key = match ($found['provider']) {
            'jsonld', 'careers' => 'site-'.Str::slug((string) parse_url($found['url'], PHP_URL_HOST)),
            default => $found['provider'].'-'.Str::slug((string) JobKey::board($found['url'])['slug']),
        };
        $source = ImportSource::where('url', $found['url'])->orWhere('key', $key)->first() ?? new ImportSource(['key' => $key]);
        $source->fill([
            'name' => $company->display_name.' ('.match ($found['provider']) { 'jsonld' => 'job pages', 'careers' => 'careers page', default => ucfirst($found['provider']) }.')',
            'provider' => $found['provider'], 'url' => $found['url'], 'allowed_domains' => $found['allowed_domains'],
            'permission_status' => 'approved', 'enabled' => true, 'publication_mode' => 'automatic', 'company_id' => $company->id,
            'permission_notes' => Str::limit($note, 900), 'settings' => [...($source->settings ?? []), ...$found['settings'], 'linked_from' => $found['linked_from']],
            'cadence_hours' => 24, 'request_delay_ms' => $found['provider'] === 'careers' || $found['provider'] === 'jsonld' ? 1500 : 1000,
            'max_requests' => $found['provider'] === 'careers' || $found['provider'] === 'jsonld' ? 60 : 20,
        ])->save();
        return $source;
    }

    private function onSite(string $url, string $host): bool
    {
        $other = preg_replace('/^www\./', '', strtolower((string) parse_url($url, PHP_URL_HOST)));
        return $other === $host || str_ends_with($other, '.'.$host);
    }

    // Every href and src on the page, as full URLs.
    private function links(string $html, string $page): array
    {
        preg_match_all('~\b(?:href|src)\s*=\s*["\']([^"\']+)["\']~i', $html, $matches);
        $links = [];
        foreach ($matches[1] as $link) {
            $link = html_entity_decode(trim($link), ENT_QUOTES | ENT_HTML5);
            if ($link === '' || preg_match('~^(?:#|mailto:|tel:|javascript:)~i', $link)) { continue; }
            $url = SourceHttp::resolveUrl($page, $link);
            if (preg_match('~^https?://~i', $url)) { $links[] = strtok($url, '#'); }
        }
        return array_values(array_unique($links));
    }

    // ATS links written inside scripts, such as an embedded Greenhouse board.
    private function rawLinks(string $html): array
    {
        preg_match_all('~(?:https?:)?//[\w.-]+\.(?:greenhouse\.io|lever\.co|ashbyhq\.com|workable\.com|recruitee\.com)[^\s"\'<>\\\\]*~i', $html, $matches);
        return array_map(fn ($link) => str_starts_with($link, '//') ? 'https:'.$link : $link, $matches[0]);
    }

    // The folder every job page sits in, such as /careers/.
    private function prefix(array $urls): string
    {
        $paths = array_map(fn ($url) => (string) parse_url($url, PHP_URL_PATH), $urls);
        $prefix = array_shift($paths);
        foreach ($paths as $path) {
            while ($prefix !== '' && ! str_starts_with($path, $prefix)) { $prefix = substr($prefix, 0, -1); }
        }
        $prefix = substr($prefix, 0, strrpos($prefix, '/') + 1);
        return $prefix !== '' ? $prefix : '/';
    }
}
