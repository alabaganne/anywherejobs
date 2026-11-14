<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpportunityResource;
use App\Importing\CompanyRegistry;
use App\Importing\OpportunityImporter;
use App\Importing\SourceHttp;
use App\Importing\SourceReader;
use App\Jobs\FetchNewOpportunities;
use App\Jobs\ImportOpportunitySource;
use App\Models\Company;
use App\Models\ImportRun;
use App\Models\ImportSource;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ImportAdminController extends Controller
{
    public function index()
    {
        return redirect()->route('imports.review');
    }

    public function sources()
    {
        return Inertia::render('Imports/Sources', [
            'sources' => ImportSource::withCount(['items', 'items as pending_count' => fn ($query) => $query->whereIn('state', ['pending', 'review'])])->orderBy('name')->get(),
            'fetch' => $this->fetchState(),
        ]);
    }

    public function companies()
    {
        $companies = Company::where(fn ($query) => $query->whereNotNull('tracked_at')->orWhereNotNull('tracking'))
            ->with(['importSources' => fn ($query) => $query->withCount(['items', 'items as published_count' => fn ($query) => $query->where('state', 'published')])])
            ->withCount(['opportunities as open_count' => fn ($query) => $query->available()])
            ->orderByRaw('tracked_at IS NULL')->orderByDesc('tracked_at')->orderByDesc('id')->limit(500)->get();
        return Inertia::render('Imports/Companies', [
            'companies' => $companies->map(fn (Company $company) => [
                'id' => $company->id, 'name' => $company->display_name, 'website' => $company->website, 'careers_url' => $company->careers_url,
                'public_url' => $company->publicUrl(), 'verified' => (bool) $company->verified_at, 'open_count' => $company->open_count,
                'tracked_at' => $company->tracked_at?->toIso8601String(), 'reason' => $company->tracked_reason, 'tracking' => $company->tracking,
                'sources' => $company->importSources->map(fn ($source) => [
                    'id' => $source->id, 'name' => $source->name, 'provider' => $source->provider, 'enabled' => $source->enabled,
                    'last_complete_at' => $source->last_complete_at?->toIso8601String(), 'last_error' => $source->last_error,
                    'items_count' => $source->items_count, 'published_count' => $source->published_count,
                ]),
            ]),
            'perDay' => config('importer.registry.per_day'),
        ]);
    }

    public function fetch(Request $request)
    {
        $data = $request->validate(['count' => 'required|integer|min:1|max:100']);
        if (FetchNewOpportunities::running()) {
            throw ValidationException::withMessages(['count' => __('A fetch is already running. Wait for it to finish.')]);
        }
        if (! FetchNewOpportunities::start($data['count'], $request->user()->name)) {
            throw ValidationException::withMessages(['count' => __('No approved source is enabled.')]);
        }
        $this->audit($request, 'fetch.queued', null, null, ['count' => $data['count']]);
        return back()->with('toast', ['type' => 'update', 'message' => __('Fetching new listings. Progress shows on this page.')]);
    }

    private function fetchState(): ?array
    {
        if (! ($state = FetchNewOpportunities::state())) { return null; }
        return [...collect($state)->only(['count', 'sources', 'read', 'failed', 'by', 'started_at', 'finished_at'])->all(),
            'days' => (int) config('importer.fetch_max_age_days'),
            'running' => FetchNewOpportunities::running(),
            'stopped' => $state['status'] === 'running' && ! FetchNewOpportunities::running(),
            'added' => Opportunity::with('company.user')->whereIn('id', $state['added'])->latest('id')->get()
                ->map(fn ($role) => ['id' => $role->id, 'title' => $role->title, 'company' => $role->company?->display_name, 'kind' => $role->kind, 'public_url' => $role->public_url]),
        ];
    }

    public function review(Request $request)
    {
        $filters = $request->validate([
            'source_id' => 'nullable|integer|exists:import_sources,id',
            'state' => 'nullable|in:all,pending,review,queued,waiting,unmatched,lead,published,closed,rejected',
            'page' => 'nullable|integer|min:1|max:10000',
        ]);
        $filters['state'] ??= 'review';
        $bySource = fn ($query) => $query->when($filters['source_id'] ?? null, fn ($query, $source) => $query->where('import_source_id', $source));
        $items = OpportunitySource::with(['importSource', 'opportunity.company.user'])->tap($bySource)
            ->when($filters['state'] !== 'all', fn ($query) => $query->where('state', $filters['state']))
            ->latest('id')->paginate(20)->withQueryString();
        return Inertia::render('Imports/Review', [
            'items' => $items->through(fn ($item) => [
                'id' => $item->id, 'source_name' => $item->importSource->name,
                'source_url' => $item->source_url, 'state' => $item->state, 'error' => $item->error,
                'post_url' => $item->payload['post']['url'] ?? null,
                'last_verified_at' => $item->last_verified_at?->toIso8601String(),
                'normalized_data' => $item->normalized_data,
                'opportunity' => $item->opportunity ? [
                    'id' => $item->opportunity_id, 'title' => $item->opportunity->title,
                    'public_url' => $item->opportunity->public_url, 'publication_status' => $item->opportunity->publication_status,
                ] : null,
            ]),
            'counts' => OpportunitySource::query()->tap($bySource)->selectRaw('state, count(*) as total')->groupBy('state')->pluck('total', 'state'),
            'sources' => ImportSource::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function reports()
    {
        return Inertia::render('Imports/Reports', [
            'reports' => DB::table('opportunity_reports')->join('opportunities', 'opportunities.id', '=', 'opportunity_reports.opportunity_id')
                ->where('opportunity_reports.status', 'open')->select('opportunity_reports.*', 'opportunities.title')->orderBy('opportunity_reports.id')->limit(100)->get(),
        ]);
    }

    public function claims()
    {
        return Inertia::render('Imports/Claims', [
            'claims' => DB::table('company_claims')->join('users', 'users.id', '=', 'company_claims.user_id')->leftJoin('companies', 'companies.id', '=', 'company_claims.company_id')
                ->where('company_claims.status', 'pending')->select('company_claims.*', 'users.name as claimant_name', 'users.email as claimant_email', 'companies.name as company_name')
                ->orderBy('company_claims.id')->limit(100)->get(),
        ]);
    }

    public function scheduleToggle(\Illuminate\Http\Request $request, string $task)
    {
        abort_unless(isset(\App\Support\ScheduledTasks::COMMANDS[$task]), 404);
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        \Illuminate\Support\Facades\Cache::forever('schedule.enabled.'.$task, $data['enabled']);
        return back();
    }

    public function scheduleRun(string $task)
    {
        $command = \App\Support\ScheduledTasks::COMMANDS[$task] ?? null;
        abort_unless($command, 404);
        \Illuminate\Support\Facades\Artisan::queue($command);
        return back()->with('success', __('Task queued.'));
    }

    public function runs()
    {
        return Inertia::render('Imports/Runs', [
            'scheduledTasks' => \App\Support\ScheduledTasks::listing(),
            'runs' => ImportRun::with('importSource:id,name')->latest()->paginate(25),
            'deliveryHealth' => DB::table('opportunity_deliveries')->selectRaw('status, count(*) as total')->groupBy('status')->get(),
            'uncertainDeliveries' => DB::table('opportunity_deliveries')->where('status', 'uncertain')->select('batch_id', 'saved_search_id', 'last_error', 'attempted_at')->distinct()->limit(20)->get(),
        ]);
    }

    public function opportunity(Opportunity $opportunity)
    {
        $opportunity->load(['company.user', 'sources.importSource']);
        return Inertia::render('Imports/Moderate', [
            'opportunity' => [
                ...$opportunity->only(['id', 'title', 'description', 'kind', 'location', 'country_code', 'applicant_scope', 'remote_eligibility', 'relocation', 'visa_sponsorship', 'status', 'publication_status', 'origin', 'merged_into_id']),
                'company' => $opportunity->company?->display_name,
                'public_url' => $opportunity->public_url,
            ],
            'sources' => $opportunity->sources->map(fn ($item) => [
                'id' => $item->id, 'source_name' => $item->importSource?->name, 'source_url' => $item->source_url, 'state' => $item->state,
            ]),
            'reports' => DB::table('opportunity_reports')->where('opportunity_id', $opportunity->id)->where('status', 'open')->orderBy('id')->get(['id', 'reason', 'details', 'created_at']),
        ]);
    }

    public function createSource()
    {
        return Inertia::render('Imports/SourceForm');
    }

    public function editSource(ImportSource $source)
    {
        return Inertia::render('Imports/SourceForm', ['source' => $source]);
    }

    public function storeSource(Request $request)
    {
        $source = ImportSource::create($this->sourceData($request));
        $this->audit($request, 'source.created', null, $source->id, ['permission_status' => $source->permission_status]);
        return redirect()->route('imports.sources.index')->with('toast', ['type' => 'store', 'message' => __('Source configuration saved.')]);
    }

    public function updateSource(Request $request, ImportSource $source)
    {
        $data = $this->sourceData($request, $source);
        DB::transaction(function () use ($source, $data, $request) {
            $source->update($data);
            if ($source->permission_status !== 'approved') {
                $roles = Opportunity::where('origin', 'imported')->whereHas('sources', fn ($query) => $query->where('import_source_id', $source->id))->get();
                foreach ($roles as $role) {
                    $hasApprovedSource = $role->sources()->where('state', 'published')->whereHas('importSource', fn ($query) => $query->where('permission_status', 'approved'))->exists();
                    if (! $hasApprovedSource) {
                        $role->update(['publication_status' => 'suppressed']);
                    }
                }
            }
            $this->audit($request, 'source.updated', null, $source->id, ['permission_status' => $source->permission_status, 'enabled' => $source->enabled]);
        });
        $fromEditPage = url()->previous() === route('imports.sources.edit', $source);
        return ($fromEditPage ? redirect()->route('imports.sources.index') : back())->with('toast', ['type' => 'update', 'message' => __('Source updated.')]);
    }

    private function sourceData(Request $request, ?ImportSource $source = null): array
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9_-]*$/', Rule::unique('import_sources')->ignore($source?->id)],
            'name' => 'required|string|max:255',
            'provider' => 'required|in:jsonld,greenhouse,lever,ashby,workable,recruitee,himalayas,remoteok,remotive,wwr,hn,pages',
            'url' => 'required|url:http,https|max:2048',
            // Linked job pages can be on any site, so they have no allowlist.
            'allowed_domains' => 'exclude_if:provider,pages|required|array|min:1|max:20',
            'allowed_domains.*' => 'required|string|max:253|regex:/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/',
            'permission_status' => 'required|in:pending,approved,revoked',
            'permission_notes' => 'nullable|required_if:permission_status,approved|string|min:20|max:10000',
            'publication_mode' => 'required|in:review,automatic,leads',
            'enabled' => 'required|boolean',
            'company_id' => 'nullable|integer|exists:companies,id',
            'cadence_hours' => 'required|integer|min:1|max:168',
            'request_delay_ms' => 'required|integer|min:500|max:60000',
            'max_requests' => 'required|integer|min:1|max:1000',
            'settings' => 'nullable|array',
            'settings.employer_name' => 'nullable|string|max:255',
            'settings.job_path_prefix' => 'nullable|string|max:255|starts_with:/',
            'settings.authoritative_employer' => 'nullable|boolean',
            'settings.scope' => 'nullable|in:all,reachable',
            'settings.freshness_hours' => 'nullable|integer|min:24|max:2160',
            'settings.curated_snapshot' => 'nullable|boolean',
            'settings.pages' => 'nullable|integer|min:1|max:50',
        ]);
        $data['allowed_domains'] ??= [];
        $data['settings'] = collect($data['settings'] ?? [])->only(['employer_name', 'job_path_prefix', 'authoritative_employer', 'scope', 'freshness_hours', 'curated_snapshot', 'pages'])->all();
        if (in_array($data['provider'], SourceReader::BOARDS, true) && $data['publication_mode'] !== 'leads') {
            throw ValidationException::withMessages(['publication_mode' => __('Remote job boards only feed job leads; their listings are never published.')]);
        }
        if ($source && ($source->url !== $data['url'] || $source->provider !== $data['provider'] || $source->allowed_domains !== $data['allowed_domains']) && $data['permission_status'] === 'approved' && $source->permission_notes === $data['permission_notes']) {
            throw ValidationException::withMessages(['permission_notes' => __('Record a fresh permission review when changing the source URL, provider, or allowed domains.')]);
        }
        if ($data['enabled'] && $data['permission_status'] !== 'approved') {
            throw ValidationException::withMessages(['enabled' => __('A source must have recorded reuse approval before imports are enabled.')]);
        }
        try {
            (new SourceHttp(new ImportSource($data)))->validate($data['url']);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['url' => $exception->getMessage()]);
        }
        return $data;
    }

    // One listing from the queue shown as its page would look once published, with the review actions.
    public function item(Request $request, OpportunitySource $item, OpportunityImporter $importer)
    {
        abort_unless($item->normalized_data, 404);
        $filters = $request->validate([
            'source_id' => 'nullable|integer',
            'state' => 'nullable|in:all,pending,review,queued,waiting,unmatched,lead,published,closed,rejected',
        ]);
        $filters['state'] ??= 'review';
        $item->load('importSource', 'opportunity');
        $role = $importer->preview($item);
        $opportunity = (new OpportunityResource($role))->resolve($request);
        if (! $role->company?->exists) { $opportunity['company']['public_url'] = null; }
        // The next listing down the queue, so the admin can go through it one page at a time.
        $next = OpportunitySource::whereNotNull('normalized_data')->where('id', '<', $item->id)
            ->when($filters['state'] !== 'all', fn ($query) => $query->where('state', $filters['state']))
            ->when($filters['source_id'] ?? null, fn ($query, $source) => $query->where('import_source_id', $source))
            ->orderByDesc('id')->value('id');
        $data = $item->normalized_data;
        return Inertia::render('Opportunities/Show', [
            'opportunity' => $opportunity,
            'review' => [
                'id' => $item->id, 'state' => $item->state, 'error' => $item->error,
                'source' => ['id' => $item->import_source_id, 'name' => $item->importSource->name, 'trusted' => CompanyRegistry::trusted($item->importSource),
                    'can_trust' => ! in_array($item->importSource->publication_mode, ['leads'], true) && $item->importSource->provider !== 'pages'],
                'quality' => $data['quality'] ?? null, 'quality_reason' => $data['quality_reason'] ?? null,
                'ai_reason' => $data['page_check']['reason'] ?? null, 'post_url' => $data['page_check']['post_url'] ?? $item->payload['post']['url'] ?? null,
                'original_url' => $data['page_check']['page_url'] ?? $item->source_url,
                'checked_at' => $item->last_verified_at?->toIso8601String(),
                'public_url' => $item->opportunity?->publication_status === 'published' ? $item->opportunity->public_url : null,
                'next_id' => $next, 'filters' => array_filter($filters),
            ],
        ]);
    }

    // Approves this listing and trusts its source: its new listings go live from now on without review.
    public function trust(Request $request, OpportunitySource $item, OpportunityImporter $importer)
    {
        $source = $item->importSource;
        abort_if($source->publication_mode === 'leads' || $source->provider === 'pages', 422, __('This source cannot be trusted as a whole.'));
        if ($item->state !== 'published') {
            try {
                $importer->publish($item);
            } catch (\RuntimeException $exception) {
                throw ValidationException::withMessages(['publication' => $exception->getMessage()]);
            }
        }
        $source->update(['settings' => [...($source->settings ?? []), 'trusted' => true], 'publication_mode' => 'automatic']);
        $published = 0;
        // The other listings of this source waiting for review go live too; ones too old to publish are read again on the next run.
        $source->items()->where('state', 'review')->whereNull('opportunity_id')->whereNotNull('normalized_data')->orderBy('id')->limit(200)->get()
            ->each(function (OpportunitySource $other) use ($importer, &$published) {
                if (($other->normalized_data['quality'] ?? null) === 'reject') { return; }
                try {
                    $importer->publish($other);
                    $published++;
                } catch (\RuntimeException) {
                    $other->update(['state' => 'pending']);
                }
            });
        $this->audit($request, 'source.trusted', null, $source->id, ['item_id' => $item->id, 'published' => $published]);
        return back()->with('toast', ['type' => 'update', 'message' => trans_choice('Approved. :source is trusted now, and :count more waiting listing went live.|Approved. :source is trusted now, and :count more waiting listings went live.', $published, ['source' => $source->name])]);
    }

    public function runSource(Request $request, ImportSource $source)
    {
        abort_unless($source->isApproved(), 422, __('Enable this source only after recording reuse approval.'));
        $data = $request->validate(['recheck' => 'nullable|boolean']);
        ImportOpportunitySource::dispatch($source->id, 40, (bool) ($data['recheck'] ?? false));
        $this->audit($request, 'source.queued', null, $source->id, ['recheck' => (bool) ($data['recheck'] ?? false)]);
        return back()->with('toast', ['type' => 'update', 'message' => __('Import queued. Source limits and robots rules still apply.')]);
    }

    public function publish(Request $request, OpportunitySource $item, OpportunityImporter $importer)
    {
        try {
            $published = $importer->publish($item);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['publication' => $exception->getMessage()]);
        }
        $this->audit($request, 'opportunity.published', $published->id, $item->import_source_id, ['item_id' => $item->id]);
        return back()->with('toast', ['type' => 'update', 'message' => __('Role reviewed. Suppressed listings stay hidden until explicitly restored.')]);
    }

    public function reject(Request $request, OpportunitySource $item)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($item, $data, $request) {
            $item->update(['state' => 'rejected', 'error' => $data['reason']]);
            if ($item->opportunity && ! $item->opportunity->sources()->where('id', '!=', $item->id)->where('state', 'published')->exists()) {
                $item->opportunity->update(['publication_status' => 'suppressed']);
            }
            $this->audit($request, 'source-item.rejected', $item->opportunity_id, $item->import_source_id, ['item_id' => $item->id, 'reason' => $data['reason']]);
        });
        return back();
    }

    public function moderate(Request $request, Opportunity $opportunity)
    {
        $data = $request->validate([
            'action' => 'required|in:suppress,close,restore,correct',
            'reason' => 'required|string|min:5|max:2000',
            'corrections' => 'nullable|array',
            'corrections.title' => 'sometimes|required|string|max:255',
            'corrections.description' => 'sometimes|required|string|max:100000',
            'corrections.kind' => 'sometimes|required|in:internship,job',
            'corrections.country_code' => 'nullable|string|regex:/^[A-Z]{2}$/',
            'corrections.location' => 'nullable|string|max:255',
            'corrections.remote_eligibility' => 'sometimes|required|in:eligible,restricted,unknown',
            'corrections.applicant_scope' => 'sometimes|required|in:anywhere,listed,local,unknown',
            'corrections.relocation' => 'sometimes|required|in:yes,no,unknown',
            'corrections.visa_sponsorship' => 'sometimes|required|in:yes,no,unknown',
        ]);
        DB::transaction(function () use ($opportunity, $data, $request) {
            $opportunity = Opportunity::lockForUpdate()->findOrFail($opportunity->id);
            $overrides = $opportunity->moderation_overrides ?: [];
            if ($data['action'] === 'restore') {
                abort_if($opportunity->merged_into_id, 422, __('A merged duplicate cannot be restored.'));
                if ($opportunity->origin === 'imported') {
                    abort_unless($opportunity->hasFreshSource(), 422, __('Restore requires a current approved source.'));
                }
                unset($overrides['publication_status']);
                $opportunity->update(['publication_status' => 'published', 'moderation_overrides' => $overrides]);
            } elseif ($data['action'] === 'suppress') {
                $overrides['publication_status'] = 'suppressed';
                $opportunity->update(['publication_status' => 'suppressed', 'moderation_overrides' => $overrides]);
            } elseif ($data['action'] === 'close') {
                $overrides['status'] = 'closed';
                $opportunity->update(['status' => 'closed', 'moderation_overrides' => $overrides]);
            } else {
                $corrections = collect($data['corrections'] ?? [])->only(['title', 'description', 'kind', 'country_code', 'location', 'remote_eligibility', 'applicant_scope', 'relocation', 'visa_sponsorship'])->all();
                abort_unless($corrections, 422, __('Choose a fact to correct.'));
                $opportunity->update([...$corrections, 'moderation_overrides' => array_merge($overrides, $corrections)]);
            }
            $this->audit($request, 'opportunity.'.$data['action'], $opportunity->id, null, ['reason' => $data['reason'], 'corrections' => $data['corrections'] ?? []]);
        });
        return back()->with('toast', ['type' => 'update', 'message' => __('Moderation saved. Import refreshes retain reviewed corrections.')]);
    }

    public function resolveReport(Request $request, int $report)
    {
        $data = $request->validate(['status' => 'required|in:resolved,dismissed', 'notes' => 'required|string|min:5|max:2000']);
        $record = DB::table('opportunity_reports')->find($report);
        abort_unless($record, 404);
        DB::transaction(function () use ($request, $report, $record, $data) {
            DB::table('opportunity_reports')->where('id', $report)->update(['status' => $data['status'], 'updated_at' => now()]);
            $this->audit($request, 'report.'.$data['status'], $record->opportunity_id, null, ['report_id' => $report, 'notes' => $data['notes']]);
        });
        return back();
    }

    public function merge(Request $request, Opportunity $opportunity)
    {
        $data = $request->validate(['survivor_id' => 'required|integer|exists:opportunities,id', 'reason' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($opportunity, $data, $request) {
            $ids = [$opportunity->id, (int) $data['survivor_id']];
            $roles = Opportunity::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $duplicate = $roles->get($opportunity->id);
            $survivor = $roles->get((int) $data['survivor_id']);
            abort_if($duplicate->id === $survivor->id || $duplicate->merged_into_id || $survivor->merged_into_id, 422, __('Select two distinct, unmerged roles.'));
            abort_unless($duplicate->company_id === $survivor->company_id, 422, __('Verify and consolidate the employer identity before merging different companies.'));
            abort_if($duplicate->origin === 'native' && $survivor->origin !== 'native', 422, __('Keep the employer-managed role as the survivor.'));
            foreach (DB::table('likes')->where('opportunity_id', $duplicate->id)->get() as $like) {
                DB::table('likes')->insertOrIgnore(['candidate_id' => $like->candidate_id, 'opportunity_id' => $survivor->id]);
            }
            DB::table('likes')->where('opportunity_id', $duplicate->id)->delete();
            DB::table('applications')->where('opportunity_id', $duplicate->id)->update(['opportunity_id' => $survivor->id, 'company_id' => $survivor->company_id]);
            OpportunitySource::where('opportunity_id', $duplicate->id)->update(['opportunity_id' => $survivor->id]);
            DB::table('opportunity_reports')->where('opportunity_id', $duplicate->id)->update(['opportunity_id' => $survivor->id]);
            $deliveryHistory = [];
            foreach (DB::table('opportunity_deliveries')->where('opportunity_id', $duplicate->id)->lockForUpdate()->get() as $delivery) {
                $existing = DB::table('opportunity_deliveries')->where('opportunity_id', $survivor->id)->where('user_id', $delivery->user_id)->lockForUpdate()->first();
                if (! $existing) {
                    DB::table('opportunity_deliveries')->where('id', $delivery->id)->update(['opportunity_id' => $survivor->id]);
                    continue;
                }
                $priority = ['sent' => 5, 'uncertain' => 4, 'sending' => 3, 'pending' => 2, 'cancelled' => 1];
                if (($priority[$delivery->status] ?? 0) > ($priority[$existing->status] ?? 0)) {
                    DB::table('opportunity_deliveries')->where('id', $existing->id)->update([
                        'status' => $delivery->status, 'sent_at' => $delivery->sent_at, 'batch_id' => $delivery->batch_id,
                        'saved_search_id' => $delivery->saved_search_id, 'attempts' => $delivery->attempts,
                        'attempted_at' => $delivery->attempted_at, 'last_error' => $delivery->last_error,
                    ]);
                }
                $deliveryHistory[] = ['removed_id' => $delivery->id, 'retained_id' => $existing->id, 'status' => $delivery->status, 'batch_id' => $delivery->batch_id];
                DB::table('opportunity_deliveries')->where('id', $delivery->id)->delete();
            }
            $duplicate->update(['merged_into_id' => $survivor->id, 'publication_status' => 'suppressed', 'status' => 'closed']);
            $this->audit($request, 'opportunity.merged', $survivor->id, null, ['duplicate_id' => $duplicate->id, 'reason' => $data['reason'], 'delivery_history' => $deliveryHistory]);
        });
        return back()->with('toast', ['type' => 'update', 'message' => __('Duplicate merged. Saved roles, application history, and the old URL are preserved.')]);
    }

    public function reviewClaim(Request $request, int $claim)
    {
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'review_notes' => 'required|string|min:20|max:5000']);
        DB::transaction(function () use ($request, $claim, $data) {
            $record = DB::table('company_claims')->where('id', $claim)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 404);
            if ($data['status'] === 'approved') {
                $claimant = User::with('userable')->findOrFail($record->user_id);
                abort_unless($claimant->isCompany(), 422, __('The claimant needs an existing employer account.'));
                $company = Company::with('user')->lockForUpdate()->findOrFail($record->company_id);
                abort_if($company->user || $company->claimed_by_company_id || $company->id === $claimant->userable_id, 422, __('This company already has an account or a verified claim.'));
                $target = $claimant->userable;
                DB::table('opportunities')->where('company_id', $company->id)->update(['company_id' => $target->id]);
                DB::table('applications')->where('company_id', $company->id)->update(['company_id' => $target->id]);
                ImportSource::where('company_id', $company->id)->update(['company_id' => $target->id]);
                $company->forceFill(['claimed_by_company_id' => $target->id])->save();
            }
            DB::table('company_claims')->where('id', $claim)->update([...$data, 'updated_at' => now()]);
            $this->audit($request, 'company-claim.'.$data['status'], null, null, ['claim_id' => $claim, 'company_id' => $record->company_id, 'review_notes' => $data['review_notes']]);
        });
        return back()->with('toast', ['type' => 'update', 'message' => __('Claim review saved. Imported applications remain external.')]);
    }

    private function audit(Request $request, string $action, ?int $opportunityId, ?int $source, array $details): void
    {
        DB::table('opportunity_audits')->insert([
            'user_id' => $request->user()->id, 'opportunity_id' => $opportunityId, 'import_source_id' => $source,
            'action' => $action, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
