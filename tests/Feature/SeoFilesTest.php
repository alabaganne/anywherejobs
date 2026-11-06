<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_site_name_and_logo_come_from_config()
    {
        // config/site.php reads the name when config loads, so load it again after the change.
        config(['app.name' => 'Renamed Site']);
        config(['site' => require config_path('site.php')]);
        config(['site.brand.logo' => '/brand/other.svg']);

        $this->get('/site.webmanifest')->assertOk()->assertJsonPath('name', 'Renamed Site')->assertJsonPath('icons.0.src', config('site.brand.icon_small'));
        $this->get('/about')->assertOk()
            ->assertInertia(fn ($page) => $page->where('brand.name', 'Renamed Site')->where('brand.logo', '/brand/other.svg'))
            ->assertSee('Renamed Site', false)
            ->assertDontSee('Anywhere Jobs', false);
    }

    public function test_robots_allows_public_pages_and_blocks_private_areas()
    {
        $robots = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();
        $this->assertStringContainsString("User-agent: *\nAllow: /\n", $robots);
        $this->assertStringContainsString("Disallow: /dashboard\n", $robots);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);
        $this->assertStringNotContainsString('Disallow: /companies', $robots);
    }

    public function test_llms_txt_summarises_the_site_and_links_guides()
    {
        $this->get('/llms.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# '.config('app.name'), false)
            ->assertSee(url('/opportunities?worldwide=1'), false)
            ->assertDontSee('remote-from-tunisia', false)
            ->assertSee(url('/blog/how-to-find-an-internship-as-a-student.md'), false)
            ->assertSee(url('/fr/blog/trouver-un-stage-etudiant.md'), false);
    }

    public function test_indexnow_is_off_without_a_key()
    {
        config(['services.indexnow.key' => null]);
        Http::fake();
        $this->get('/indexnow-key.txt')->assertNotFound();
        $this->artisan('seo:indexnow')->expectsOutputToContain('IndexNow is off')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_indexnow_sends_public_urls_with_its_key()
    {
        config(['services.indexnow.key' => 'test-indexnow-key-123456']);
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);
        $this->get('/indexnow-key.txt')->assertOk()->assertSee('test-indexnow-key-123456');

        $this->artisan('seo:indexnow', ['--all' => true])->assertSuccessful();

        Http::assertSent(fn ($request) => $request['key'] === 'test-indexnow-key-123456'
            && $request['keyLocation'] === url('/indexnow-key.txt')
            && in_array(url('/fr/blog/trouver-un-stage-etudiant'), $request['urlList'], true));
    }
}
