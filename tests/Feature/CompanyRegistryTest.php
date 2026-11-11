<?php

namespace Tests\Feature;

use App\Ai\OpportunityClassifier;
use App\Importing\CompanyRegistry;
use App\Importing\JobKey;
use App\Importing\OpportunityImporter;
use App\Importing\SourceHttp;
use App\Importing\SourceReader;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-26 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        Field::create(['name' => 'Web Development']);
        config(['importer.scope' => null]);
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_a_company_whose_careers_page_links_to_its_greenhouse_board_is_followed_and_its_jobs_published(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->job(1, 'Frontend Engineer')]]),
            'acme.dev/careers' => Http::response('<html><body><h1>Join Acme</h1><a href="https://job-boards.greenhouse.io/acme">See open roles</a></body></html>'),
            'acme.dev*' => Http::response('<html><title>Acme</title><body>Acme builds payment tools.</body></html>'),
        ]);

        $result = app(CompanyRegistry::class)->add(['company' => 'Acme', 'website' => 'https://acme.dev', 'careers_url' => 'https://acme.dev/careers', 'reason' => 'Visa sponsor, Berlin']);

        $this->assertSame('added', $result[0]);
        $company = Company::sole();
        $source = ImportSource::sole();
        $this->assertNotNull($company->verified_at);
        $this->assertNotNull($company->tracked_at);
        $this->assertSame(['Visa sponsor, Berlin', 'greenhouse', 'https://acme.dev/careers'], [$company->tracked_reason, $company->tracking['method'], $company->tracking['linked_from']]);
        $this->assertSame(['greenhouse-acme', 'approved', true, $company->id], [$source->key, $source->permission_status, $source->enabled, $source->company_id]);
        $this->assertSame('https://acme.dev/careers', $source->settings['linked_from']);

        config(['importer.trusted_sources' => true]);
        app(OpportunityImporter::class)->run($source);
        $role = Opportunity::sole();
        $this->assertSame([$company->id, 'Frontend Engineer'], [$role->company_id, $role->title]);
        $this->assertSame('Already followed.', app(CompanyRegistry::class)->add(['company' => 'Acme', 'website' => 'https://www.acme.dev'])[2]);
    }

    public function test_job_pages_with_structured_data_on_the_company_site_become_a_job_pages_source(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'acme.dev/careers/backend-engineer' => Http::response('<html><script type="application/ld+json">'.json_encode(['@type' => 'JobPosting', 'title' => 'Backend Engineer']).'</script></html>'),
            'acme.dev/careers' => Http::response('<html><body><a href="/careers/backend-engineer">Backend Engineer</a></body></html>'),
            'acme.dev*' => Http::response('<html><title>Acme</title><body><a href="/careers">Careers</a></body></html>'),
        ]);

        $this->assertSame('added', app(CompanyRegistry::class)->add(['company' => 'Acme', 'website' => 'https://acme.dev', 'careers_url' => ''])[0]);

        $source = ImportSource::sole();
        $this->assertSame(['jsonld', 'https://acme.dev/careers', '/careers/'], [$source->provider, $source->url, $source->settings['job_path_prefix']]);
        $this->assertSame(['acme.dev', 'www.acme.dev'], $source->allowed_domains);
    }

    public function test_a_company_with_no_job_list_on_its_site_is_not_followed(): void
    {
        Http::fake(['*/robots.txt' => Http::response('', 404), 'acme.dev*' => Http::response('<html><title>Acme</title><body>We build payment tools.</body></html>')]);

        $result = app(CompanyRegistry::class)->add(['company' => 'Acme', 'website' => 'https://acme.dev', 'careers_url' => '']);

        $this->assertSame(['skipped', 'Not added: No job list was found on the company website.'], [$result[0], $result[2]]);
        $this->assertNull(Company::sole()->tracked_at);
        $this->assertFalse(ImportSource::exists());
        $this->assertSame('skipped', app(CompanyRegistry::class)->add(['company' => 'Shop', 'website' => 'https://shop.example.tn'])[0]);
        $this->assertSame('skipped', app(CompanyRegistry::class)->add(['company' => 'Acme', 'website' => 'https://www.linkedin.com/company/acme'])[0]);
    }

    public function test_the_ai_careers_reader_keeps_only_jobs_whose_link_and_title_are_on_the_page(): void
    {
        $this->aiOn();
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'api.openai.com/v1/responses' => Http::response($this->reply(['jobs' => [
                ['title' => 'Senior Frontend Developer', 'url' => 'https://acme.dev/careers/frontend', 'location' => 'Berlin'],
                ['title' => 'Data Engineer', 'url' => 'https://acme.dev/careers/data', 'location' => ''],
                ['title' => 'Head of Growth', 'url' => 'https://acme.dev/careers/growth', 'location' => 'Mars'],
                ['title' => 'Senior Frontend Developer', 'url' => 'https://acme.dev/careers/backend', 'location' => ''],
            ]])),
            'acme.dev/careers' => Http::response('<html><body><h1>Open roles</h1><ul>'
                .'<li><a href="/careers/frontend">Senior Frontend Developer</a> Berlin</li>'
                .'<li><a href="/careers/growth">Head of Growth</a> Paris</li>'
                .'<li><a href="/blog">Blog</a></li></ul></body></html>'),
        ]);
        $source = $this->source(['key' => 'site-acme-dev', 'provider' => 'careers', 'url' => 'https://acme.dev/careers', 'allowed_domains' => ['acme.dev', 'www.acme.dev']]);

        $snapshot = (new SourceReader(new SourceHttp($source)))->discover($source);

        $this->assertSame([
            ['https://acme.dev/careers/frontend', 'Senior Frontend Developer', 'Berlin'],
            ['https://acme.dev/careers/growth', 'Head of Growth', ''],
        ], array_map(fn ($item) => [$item['source_url'], $item['payload']['title'], $item['payload']['location']], $snapshot['items']));
    }

    public function test_a_careers_job_page_keeps_the_employers_own_words(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'acme.dev/careers/frontend' => Http::response('<html><body><nav>Home About</nav><main><h1>Senior Frontend Developer</h1><p>'
                .str_repeat('You will build our React checkout with a small team. ', 4).'</p></main><footer>Imprint</footer></body></html>'),
        ]);
        $company = Company::forceCreate(['name' => 'Acme', 'import_identity' => 'domain:acme.dev']);
        $source = $this->source(['key' => 'site-acme-dev', 'provider' => 'careers', 'url' => 'https://acme.dev/careers', 'allowed_domains' => ['acme.dev'], 'company_id' => $company->id]);
        $item = new OpportunitySource(['source_url' => 'https://acme.dev/careers/frontend', 'payload' => ['title' => 'Senior Frontend Developer', 'location' => 'Berlin']]);

        $data = (new SourceReader(new SourceHttp($source)))->read($source, $item);

        $this->assertSame(['Senior Frontend Developer', 'Acme', 'Berlin', 'https://acme.dev/careers/frontend'], [$data['title'], $data['employer'], $data['location'], $data['application_url']]);
        $this->assertStringContainsString('You will build our React checkout', $data['description']);
        $this->assertStringNotContainsString('Imprint', $data['description']);
    }

    public function test_new_listings_from_a_source_no_followed_company_links_to_go_to_review(): void
    {
        config(['importer.trusted_sources' => true]);
        Http::fake(['*/robots.txt' => Http::response('', 404), 'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->job(1, 'Frontend Engineer')]])]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);

        app(OpportunityImporter::class)->run($source);

        $this->assertSame(['review', 'Not linked from a checked company website.'], [OpportunitySource::sole()->state, OpportunitySource::sole()->error]);
        $this->assertSame(0, Opportunity::count());
    }

    public function test_a_listing_the_ai_rates_good_goes_live_from_any_source_and_a_weak_one_waits(): void
    {
        config(['importer.trusted_sources' => true]);
        $this->rate(['Frontend Engineer' => 'good', 'Backend Engineer' => 'weak']);
        Http::fake(['*/robots.txt' => Http::response('', 404), 'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
            $this->job(1, 'Frontend Engineer'), $this->job(2, 'Backend Engineer'),
        ]])]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);

        app(OpportunityImporter::class)->run($source);

        $this->assertSame(['Frontend Engineer'], Opportunity::pluck('title')->all());
        $weak = OpportunitySource::where('external_id', '2')->sole();
        $this->assertSame(['review', 'Not linked from a checked company website.'], [$weak->state, $weak->error]);
    }

    public function test_an_admin_reviews_a_listing_on_its_page_and_can_trust_its_source(): void
    {
        config(['importer.trusted_sources' => true]);
        Http::fake(['*/robots.txt' => Http::response('', 404), 'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
            $this->job(1, 'Frontend Engineer'), $this->job(2, 'Backend Engineer'), $this->job(3, 'Mobile Engineer'),
        ]])]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);
        app(OpportunityImporter::class)->run($source);
        [$first, $second] = OpportunitySource::orderByDesc('id')->take(2)->get();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get(route('imports.items.show', $first))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Show')
            ->where('opportunity.title', $first->normalized_data['title'])
            ->where('opportunity.description', fn ($text) => str_contains($text, 'React product features'))
            ->where('review.state', 'review')->where('review.next_id', $second->id)
            ->where('review.source.can_trust', true)->where('review.source.trusted', false));
        $this->actingAs(User::factory()->create())->get(route('imports.items.show', $first))->assertForbidden();
        $this->assertSame(0, Opportunity::count());

        $this->actingAs($admin)->post(route('imports.items.trust', $first))->assertSessionHasNoErrors();

        $this->assertSame(3, Opportunity::count());
        $this->assertTrue(CompanyRegistry::trusted($source->fresh()));
        $this->assertSame('automatic', $source->fresh()->publication_mode);
        $this->assertSame(['published'], OpportunitySource::distinct()->pluck('state')->all());
    }

    public function test_linking_keeps_a_board_its_company_site_links_to_and_pauses_one_it_does_not(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->job(1, 'Frontend Engineer')]]),
            'acme.dev*' => Http::response('<html><title>Acme</title><body><a href="https://boards.greenhouse.io/acme">Jobs</a></body></html>'),
            'other.dev*' => Http::response('<html><title>Other</title><body>Nothing to see.</body></html>'),
        ]);
        Company::forceCreate(['name' => 'Acme', 'website' => 'https://acme.dev', 'domain' => 'acme.dev', 'verified_at' => now(), 'import_identity' => 'x:1']);
        Company::forceCreate(['name' => 'Other', 'website' => 'https://other.dev', 'domain' => 'other.dev', 'verified_at' => now(), 'import_identity' => 'x:2']);
        $acme = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io'], 'settings' => ['employer_name' => 'Acme']]);
        $other = $this->source(['key' => 'greenhouse-other', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/other/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io'], 'settings' => ['employer_name' => 'Other']]);

        $this->artisan('companies:link-sources --dry-run')->expectsOutputToContain('1 linked, 1 paused')->assertSuccessful();
        $this->assertTrue($other->fresh()->enabled);

        $this->artisan('companies:link-sources')->expectsOutputToContain('1 linked, 1 paused')->assertSuccessful();

        $acme->refresh();
        $this->assertTrue(CompanyRegistry::trusted($acme));
        $this->assertSame('https://acme.dev', $acme->settings['linked_from']);
        $this->assertNotNull($acme->company->tracked_at);
        $this->assertFalse($other->fresh()->enabled);
        $this->assertStringStartsWith('Paused: the company website does not link to this source', $other->fresh()->last_error);
        $this->artisan('companies:link-sources')->expectsOutputToContain('Every enabled source is linked')->assertSuccessful();
    }

    public function test_the_daily_search_adds_the_companies_the_ai_suggests(): void
    {
        $this->aiOn();
        $call = ['type' => 'function_call', 'id' => 'fc_1', 'call_id' => 'call_1', 'name' => 'suggest_company', 'status' => 'completed',
            'arguments' => json_encode(['company' => 'Acme', 'website' => 'https://acme.dev', 'careers_url' => 'https://acme.dev/careers', 'reason' => 'Visa sponsor, Berlin', 'recent_role' => 'Frontend Engineer'])];
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->job(1, 'Frontend Engineer')]]),
            'acme.dev/careers' => Http::response('<html><body><a href="https://jobs.ashbyhq.com/acme">Roles</a><a href="https://job-boards.greenhouse.io/acme">Roles</a></body></html>'),
            'acme.dev*' => Http::response('<html><title>Acme</title></html>'),
            'api.ashbyhq.com/*' => Http::response(['jobs' => []]),
            'api.openai.com/v1/responses' => Http::sequence()
                ->push($this->reply(null, [['type' => 'web_search_call', 'id' => 'ws_1', 'status' => 'completed'], $call]))
                ->push($this->reply(['found' => true, 'website' => 'https://acme.dev', 'about' => 'Acme builds payment tools.', 'size' => '', 'founded_year' => 0,
                    'headquarters' => '', 'country_code' => '', 'stage' => '', 'linkedin_url' => '', 'careers_url' => '', 'sources' => []]))
                ->push($this->reply(null, [['type' => 'message', 'id' => 'msg_2', 'role' => 'assistant', 'status' => 'completed',
                    'content' => [['type' => 'output_text', 'text' => 'Added one company.', 'annotations' => []]]]])),
        ]);

        $this->artisan('companies:discover --count=1')->expectsOutputToContain('1 new company followed.')->assertSuccessful();

        $company = Company::sole();
        $this->assertSame(['Acme', 'greenhouse', 'Acme builds payment tools.'], [$company->name, $company->tracking['method'], $company->about]);
        $request = Http::recorded(fn ($request) => str_ends_with($request->url(), '/responses'))->first()[0]->data();
        $this->assertContains('suggest_company', collect($request['tools'])->pluck('name')->all());
    }

    public function test_board_links_are_recognised_in_every_form(): void
    {
        $this->assertSame(['provider' => 'greenhouse', 'slug' => 'acme'], JobKey::board('https://boards.greenhouse.io/embed/job_board/js?for=acme'));
        $this->assertSame(['provider' => 'greenhouse', 'slug' => 'acme'], JobKey::board('https://job-boards.greenhouse.io/acme/jobs/123'));
        $this->assertSame(['provider' => 'lever', 'slug' => 'acme'], JobKey::board('https://jobs.lever.co/acme'));
        $this->assertSame(['provider' => 'ashby', 'slug' => 'acme'], JobKey::board('https://api.ashbyhq.com/posting-api/job-board/acme'));
        $this->assertSame(['provider' => 'workable', 'slug' => 'acme'], JobKey::board('https://apply.workable.com/acme/'));
        $this->assertSame(['provider' => 'recruitee', 'slug' => 'acme'], JobKey::board('https://acme.recruitee.com/'));
        $this->assertNull(JobKey::board('https://acme.dev/careers'));
    }

    public function test_admins_see_the_followed_companies(): void
    {
        $company = Company::forceCreate(['name' => 'Acme', 'website' => 'https://acme.dev', 'verified_at' => now(), 'tracked_at' => now(), 'tracked_reason' => 'Visa sponsor',
            'tracking' => ['method' => 'greenhouse', 'linked_from' => 'https://acme.dev/careers'], 'import_identity' => 'x:1']);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs', 'allowed_domains' => ['boards-api.greenhouse.io'], 'company_id' => $company->id]);
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('imports.companies'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Imports/Companies')->where('companies.0.name', 'Acme')->where('companies.0.tracking.method', 'greenhouse')
            ->where('companies.0.sources.0.name', 'greenhouse-acme'));
    }

    private function aiOn(): void
    {
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    private function reply(?array $data, ?array $output = null): array
    {
        return ['id' => 'resp_'.uniqid(), 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.5',
            'output' => $output ?? [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }

    // The AI review rates each listing by its title.
    private function rate(array $quality): void
    {
        $classifier = new class extends OpportunityClassifier {
            public array $quality = [];

            public function enabled(): bool { return true; }

            public function classifyMany(array $listings, array $fields, array $skills = []): array
            {
                return array_map(fn ($data) => ['in_scope' => true, 'quality' => $this->quality[$data['title']] ?? 'weak', 'quality_reason' => '', 'field' => 'Web Development',
                    'kind' => 'job', 'seniority' => 'mid', 'work_type' => 'remote', 'employment_types' => [], 'program_type' => 'none', 'skills' => [],
                    'applicant_scope' => 'unknown', 'eligible_countries' => [], 'excluded_countries' => [], 'tunisia' => 'unknown', 'relocation' => 'unknown',
                    'visa_sponsorship' => 'unknown', 'evidence' => '', 'model' => 'test'], $listings);
            }
        };
        $classifier->quality = $quality;
        $this->app->instance(OpportunityClassifier::class, $classifier);
    }

    private function source(array $attributes): ImportSource
    {
        return ImportSource::create($attributes + ['name' => $attributes['key'], 'permission_status' => 'approved', 'permission_notes' => 'Test.',
            'publication_mode' => 'automatic', 'enabled' => true, 'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100]);
    }

    private function job(int $id, string $title): array
    {
        return ['id' => $id, 'title' => $title, 'company_name' => 'Acme', 'location' => ['name' => 'Remote'],
            'content' => htmlspecialchars('<p>'.str_repeat('You will build and ship React product features with the team. ', 3).'</p>'),
            'absolute_url' => "https://job-boards.greenhouse.io/acme/jobs/{$id}", 'first_published' => now()->subDay()->toIso8601String()];
    }
}
