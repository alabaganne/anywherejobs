<?php

namespace App\Importing;

use App\Ai\Agents\CareersPageReader;
use App\Ai\OpportunityClassifier;
use App\Models\ImportSource;
use App\Models\OpportunitySource;
use Illuminate\Support\Str;
use RuntimeException;

// Reads a careers page that has no ATS board or JobPosting data. The AI lists the openings, and only jobs whose
// link is on the page and whose title and place appear in the page text are kept.
class CareersReader
{
    public function __construct(private SourceHttp $http, private SourceReader $reader) {}

    public function discover(ImportSource $source): array
    {
        $classifier = app(OpportunityClassifier::class);
        if (! $classifier->enabled()) { throw new RuntimeException('Reading a careers page needs the AI, and it is off.'); }
        $html = $this->http->get($source->url);
        $links = $this->links($html, $source->url);
        if (! $links) { throw new RuntimeException('The careers page has no links to jobs.'); }
        $text = (new OpportunityNormalizer())->text($html);
        $prompt = "Careers page: {$source->url}\n\nLinks on the page:\n"
            .collect($links)->map(fn ($label, $url) => '- '.($label !== '' ? $label.' → ' : '').$url)->take(300)->implode("\n")
            ."\n\nPage text:\n".Str::limit($text, 15000);
        $answer = (new CareersPageReader)->prompt($prompt, provider: $classifier->provider(), model: $classifier->model('review'))->toArray();
        $haystack = $this->plain($text.' '.implode(' ', $links));
        $items = [];
        foreach (array_slice($answer['jobs'] ?? [], 0, 200) as $job) {
            $url = trim((string) ($job['url'] ?? ''));
            $title = Str::limit(Str::squish((string) ($job['title'] ?? '')), 250, '');
            if (! isset($links[$url]) || $title === '' || ! str_contains($haystack, $this->plain($title))) { continue; }
            $location = Str::limit(Str::squish((string) ($job['location'] ?? '')), 200, '');
            if ($location !== '' && ! str_contains($haystack, $this->plain($location))) { $location = ''; }
            $items[$url] = ['external_id' => hash('sha256', $url), 'source_url' => $url, 'payload' => ['title' => $title, 'location' => $location]];
        }
        // The AI can miss a job on one day, so a missing job is only closed after the usual two missed reads.
        return ['items' => array_values($items), 'complete' => true, 'authoritative' => true];
    }

    public function read(ImportSource $source, OpportunitySource $item): array
    {
        $html = $this->http->get($item->source_url);
        $employer = $source->settings['employer_name'] ?? $source->company?->display_name;
        if ($posting = $this->reader->jobPosting($html)) {
            $data = $this->reader->fromJobPosting($source, $item, $posting);
            // The company we checked names the employer, not the page's own data.
            return ['employer' => $employer ?: $data['employer']] + $data;
        }
        // The employer's own words: the main part of the job page, without menus, scripts or footers.
        $body = preg_replace('~<(script|style|noscript|svg|nav|header|footer|form|iframe)\b[^>]*>.*?</\1>~is', ' ', $html);
        if (preg_match('~<main\b[^>]*>(.*)</main>~is', $body, $main) || preg_match('~<article\b[^>]*>(.*)</article>~is', $body, $main)) { $body = $main[1]; }
        elseif (preg_match('~<body\b[^>]*>(.*)</body>~is', $body, $main)) { $body = $main[1]; }
        return [
            'title' => $item->payload['title'] ?? '', 'description' => $body, 'employer' => $employer,
            'location' => ($item->payload['location'] ?? '') ?: null, 'application_url' => $item->source_url,
        ];
    }

    // Links to pages on the careers page's own site, with their text: [url => label].
    private function links(string $html, string $page): array
    {
        preg_match_all('~<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)</a>~is', $html, $matches, PREG_SET_ORDER);
        $hosts = $this->http->allowedHosts();
        $links = [];
        foreach ($matches as [, $href, $label]) {
            $href = html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5);
            if ($href === '' || preg_match('~^(?:#|mailto:|tel:|javascript:)~i', $href)) { continue; }
            $url = strtok(SourceHttp::resolveUrl($page, $href), '#');
            if (! in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), $hosts, true) || rtrim($url, '/') === rtrim($page, '/')) { continue; }
            $label = Str::limit(Str::squish(html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5)), 200, '');
            $links[$url] = trim(($links[$url] ?? '').' '.$label);
        }
        return $links;
    }

    private function plain(string $text): string
    {
        return ' '.Str::squish(preg_replace('/[^a-z0-9+#]+/', ' ', mb_strtolower(Str::ascii($text)))).' ';
    }
}
