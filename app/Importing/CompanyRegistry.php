<?php

namespace App\Importing;

use App\Ai\Agents\CompanyScout;
use App\Ai\OpportunityClassifier;
use App\Ai\Tools\SuggestCompany;
use App\Models\Company;
use App\Models\ImportSource;
use Illuminate\Support\Str;

// The companies the site follows. Each one is checked on its own website first, then read from where that site
// lists its jobs: its ATS board, JobPosting data on its job pages, or its careers page.
class CompanyRegistry
{
    public array $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'web_searches' => 0];

    public function __construct(private OpportunityClassifier $classifier, private CompanyVerifier $verifier, private CareersFinder $finder) {}

    public function enabled(): bool
    {
        return $this->classifier->enabled();
    }

    // Asks the AI for new companies to follow. Returns one row per suggestion: [status, name, message].
    public function discover(int $count): array
    {
        $suggest = new SuggestCompany($this, $count);
        $response = (new CompanyScout($suggest))->prompt($this->prompt($count), provider: $this->classifier->provider(), model: $this->classifier->model('discovery'));
        $this->usage = [
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'web_searches' => $response->steps->sum(fn ($step) => collect($step->providerToolCalls)->whereIn('type', ['web_search_call', 'server_tool_use'])->count()),
        ];
        return $suggest->results;
    }

    // Adds one suggested company: [status, name, message].
    public function add(array $input): array
    {
        $name = Str::limit(Str::squish((string) ($input['company'] ?? '')), 120, '');
        $website = $this->verifier->site((string) ($input['website'] ?? ''));
        if ($name === '') { return ['skipped', '', 'Not added: the company name is missing.']; }
        if (! $website) { return ['skipped', $name, 'Not added: give the home page of the company\'s own website.']; }
        $host = preg_replace('/^www\./', '', strtolower((string) parse_url($website, PHP_URL_HOST)));
        if (str_ends_with($host, '.tn')) { return ['skipped', $name, 'Not added: '.config('app.name').' no longer reads Tunisian sites.']; }

        $company = Company::where('domain', $host)->first()
            ?? Company::where('name_key', JobKey::company($name))->get()->first(fn ($company) => ! $company->domain || $company->domain === $host);
        if ($company?->claimed_by_company_id) { $company = Company::find($company->claimed_by_company_id) ?? $company; }
        if ($company?->tracked_at) { return ['skipped', $name, 'Already followed.']; }
        if ($company && ! $company->verified_at && $company->research_attempts >= 2) { return ['skipped', $name, 'Not added: its website failed the check before.']; }

        $careers = trim((string) ($input['careers_url'] ?? ''));
        $company ??= tap(new Company(), fn ($company) => $company->forceFill(['name' => $name, 'import_identity' => 'domain:'.$host])->save());
        $company->forceFill([
            'website' => $company->website ?: $website, 'domain' => $company->domain ?: $host,
            'careers_url' => $company->careers_url ?: (preg_match('~^https://~i', $careers) && strlen($careers) <= 500 ? $careers : null),
        ])->save();
        $reason = Str::limit(Str::squish((string) ($input['reason'] ?? '')), 300, '');
        return $this->track($company, $reason ?: 'Suggested by '.$this->classifier->label().'.');
    }

    // Checks the company and connects its jobs: [status, name, message].
    public function track(Company $company, string $reason): array
    {
        if (! $company->verified_at) {
            $passed = $this->verifier->verify($company);
            $company->refresh();
            if ($company->claimed_by_company_id) { $company = Company::find($company->claimed_by_company_id) ?? $company; }
            if ($passed !== true) {
                return ['skipped', (string) $company->name, 'Not added: '.($passed === null ? 'the website could not be checked.' : ($company->verification['error'] ?? 'the website check failed.'))];
            }
        }
        $found = $this->finder->find($company);
        if (isset($found['error'])) {
            $company->forceFill(['tracking' => ['error' => $found['error'], 'checked_at' => now()->toIso8601String()]])->save();
            return ['skipped', (string) $company->name, 'Not added: '.$found['error']];
        }
        $source = $this->finder->connect($company, $found, 'Linked from '.$found['linked_from'].' on '.now()->toDateString().'. '.$reason);
        $this->follow($company, $source, $found, $reason);
        $where = ['jsonld' => 'job pages', 'careers' => 'careers page'][$found['provider']] ?? ucfirst($found['provider']).' board';
        return ['added', (string) $company->name, "Added: its {$where} lists {$found['jobs']} ".Str::plural('job', $found['jobs']).'.'];
    }

    // Moves a source added before the registry under its company, once the company's own site links to it.
    // Returns [status, name, message]; a source the company site doesn't lead to is paused.
    public function link(ImportSource $source, bool $dryRun = false): array
    {
        $company = $source->company ?? $this->companyFor($source, create: ! $dryRun);
        if (! $company) { return ['skipped', $source->name, $dryRun ? 'The company is added and checked on a real run.' : 'No employer name to check.']; }
        if ($company->claimed_by_company_id) { $company = Company::find($company->claimed_by_company_id) ?? $company; }
        if (! $company->verified_at) {
            if ($dryRun) { return ['skipped', $source->name, 'The company website is not checked yet.']; }
            $passed = $this->verifier->verify($company);
            $company->refresh();
            if ($company->claimed_by_company_id) { $company = Company::find($company->claimed_by_company_id) ?? $company; }
            if ($passed === null || ($passed === false && $company->research_attempts < 2)) {
                return ['skipped', $source->name, 'The company website could not be checked yet; tried again tomorrow.'];
            }
            if ($passed === false) { return $this->pause($source, 'Paused: the company website was not found.', $dryRun); }
        }
        $found = $this->finder->find($company);
        if (isset($found['error'])) {
            return ! empty($found['retry']) ? ['skipped', $source->name, $found['error'].' Tried again tomorrow.']
                : $this->pause($source, 'Paused: the company website does not link to this source ('.$found['error'].')', $dryRun);
        }
        if (! $this->same($source, $found)) {
            if (! $dryRun) {
                $other = $this->finder->connect($company, $found, 'Linked from '.$found['linked_from'].' on '.now()->toDateString().'.');
                $this->follow($company, $other, $found, $company->tracked_reason ?: 'Linked from its own website.');
            }
            return $this->pause($source, 'Paused: the company website links to '.$found['url'].' instead.', $dryRun);
        }
        if (! $dryRun) {
            $source->update(['company_id' => $company->id, 'settings' => [...($source->settings ?? []), 'linked_from' => $found['linked_from']], 'last_error' => null]);
            $this->follow($company, $source, $found, $company->tracked_reason ?: 'Linked from its own website.');
        }
        return ['linked', $source->name, 'Linked: '.$found['linked_from'].' leads to it.'];
    }

    // Whether imports may publish this source's new listings: a followed, checked company whose own site links to it.
    public static function trusted(ImportSource $source): bool
    {
        // An admin trusted it from the review queue.
        if (! empty($source->settings['trusted'])) { return true; }
        $company = $source->company;
        if ($company?->claimed_by_company_id) { $company = Company::find($company->claimed_by_company_id) ?? $company; }
        if (! $company) { return false; }
        if ($company->user()->exists()) { return true; }
        return $company->verified_at && $company->tracked_at && ! empty($source->settings['linked_from']);
    }

    private function follow(Company $company, ImportSource $source, array $found, string $reason): void
    {
        $company->forceFill(['tracked_at' => $company->tracked_at ?? now(), 'tracked_reason' => $company->tracked_reason ?: Str::limit($reason, 300, ''),
            'tracking' => ['method' => $found['provider'], 'source_id' => $source->id, 'linked_from' => $found['linked_from'], 'jobs' => $found['jobs'] ?? null,
                'checked_at' => now()->toIso8601String()]])->save();
    }

    private function pause(ImportSource $source, string $why, bool $dryRun): array
    {
        if (! $dryRun) { $source->update(['enabled' => false, 'last_error' => Str::limit($why, 1000)]); }
        return ['paused', $source->name, $why];
    }

    // The same board, or job pages on the same site.
    private function same(ImportSource $source, array $found): bool
    {
        if ($source->provider !== $found['provider']) { return false; }
        if (in_array($source->provider, ['jsonld', 'careers'], true)) {
            return parse_url($source->url, PHP_URL_HOST) === parse_url($found['url'], PHP_URL_HOST);
        }
        return JobKey::board($source->url) == JobKey::board($found['url']);
    }

    // The employer behind a source added before the registry: the company of its listings, or the employer name it carries.
    private function companyFor(ImportSource $source, bool $create = true): ?Company
    {
        $id = $source->items()->whereNotNull('opportunity_id')->join('opportunities', 'opportunities.id', '=', 'opportunity_sources.opportunity_id')
            ->selectRaw('opportunities.company_id, count(*) as total')->groupBy('opportunities.company_id')->orderByDesc('total')->value('opportunities.company_id');
        if ($id && ($company = Company::find($id))) { return $company; }
        $name = $source->settings['employer_name'] ?? $source->items()->whereNotNull('normalized_data')->latest('id')->limit(20)->get(['normalized_data'])
            ->pluck('normalized_data.employer')->filter()->countBy()->sortDesc()->keys()->first()
            // "Affirm (Greenhouse)" names its employer too.
            ?? (trim(preg_replace('/\s*\([^)]*\)$/', '', (string) $source->name)) ?: null);
        if (! $name) { return null; }
        return Company::where('name_key', JobKey::company($name))->first()
            ?? ($create ? tap(new Company(), fn ($company) => $company->forceFill(['name' => $name, 'import_identity' => 'source:'.$source->id])->save()) : null);
    }

    private function prompt(int $count): string
    {
        $known = Company::where(fn ($query) => $query->whereNotNull('tracked_at')->orWhere('research_attempts', '>=', 2))
            ->orderByDesc('id')->limit(300)->get(['name', 'domain'])->map(fn ($company) => trim($company->name.' '.$company->domain))->implode("\n");
        $brief = config('importer.registry.brief');
        return str_replace(':site', config('app.name'), <<<PROMPT
Find {$count} companies for :site to follow.

{$brief}

Each company must:
- have posted at least one of these IT roles in the last 14 days, and list it on its own website (a careers page, or a job board that the website links to);
- be the employer itself, not a recruiting agency, staffing firm, outsourcing marketplace or job board.

Use web search to confirm each company is real and hiring now. Call suggest_company once per company; it checks the website and careers page and tells you whether the company was added. When one is not added, find another. Do not suggest Tunisian companies or .tn sites, or anything in this list:
{$known}
PROMPT);
    }
}
