<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Support\Blog;
use App\Support\PublicSeo;

class SeoFilesController extends Controller
{
    private const PRIVATE_PATHS = ['/app/', '/dashboard', '/messages', '/applications', '/settings', '/profile', '/likes', '/saved', '/notifications', '/admin', '/alerts/', '/students', '/fields', '/skills', '/password'];

    public function robots()
    {
        $base = rtrim(config('app.url'), '/');
        $lines = ['# Search engines and AI assistants are welcome to read public pages.', "# Plain-text summary for language models: {$base}/llms.txt", '', 'User-agent: *', 'Allow: /'];
        foreach (self::PRIVATE_PATHS as $path) $lines[] = 'Disallow: '.$path;
        $lines[] = '';
        $lines[] = "Sitemap: {$base}/sitemap.xml";
        return $this->text(implode("\n", $lines)."\n");
    }

    public function llms()
    {
        $base = rtrim(config('app.url'), '/');
        $brand = config('site.brand');
        $copy = PublicSeo::copy('en');
        $open = Opportunity::available()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind');
        $link = fn (string $title, string $path, string $note = '') => "- [{$title}]({$base}{$path})".($note ? ": {$note}" : '');

        $lines = [
            '# '.config('app.name'), '',
            '> '.$brand['description'], '',
            'Key facts:',
            '- Website: '.$base,
            '- Audience: everyone looking for work, from students (PFE and summer internships, graduate programmes, first jobs) to experienced people (full-time, part-time, contract and remote roles).',
            '- Price: browsing, saving, applying, alerts and tracking are free. AI tools that fill a profile from a resume and tailor a CV and cover letter to a role are free while '.config('app.name').' launches.',
            '- Coverage: internships and jobs around the world. Each listing says who can apply when the source does, and an "Open worldwide" filter shows roles anyone can apply to.',
            '- Sources: employers publish directly on '.config('app.name').'; other listings come from approved public sources, are checked daily, and always link to the original listing and application page.',
            '- Open listings right now: '.(int) ($open['internship'] ?? 0).' internships and '.(int) ($open['job'] ?? 0).' jobs.',
            '- Languages: every page and every listing in French and English; listings keep their original text too.',
            '- Founder: '.$brand['founder']['name'].' ('.$brand['founder']['url'].')',
            '- Contact: '.$brand['email'],
            '',
            '## Find roles',
            $link('All internships and jobs', '/opportunities', $copy['opportunities']['description']),
            $link('Internships', '/opportunities/internships', $copy['internships']['description']),
            $link('Jobs', '/opportunities/jobs', $copy['jobs']['description']),
            $link('Open worldwide', '/opportunities?worldwide=1', 'Internships and jobs that people in any country can apply to.'),
            $link('Version française', '/fr', PublicSeo::copy('fr')['home']['description']),
        ];
        foreach (['en' => 'Career guides (English)', 'fr' => 'Guides carrière (français)'] as $locale => $heading) {
            $posts = collect(Blog::all($locale))->reject(fn ($post) => $post['draft']);
            if ($posts->isEmpty()) continue;
            $lines[] = '';
            $lines[] = '## '.$heading;
            foreach ($posts as $post) $lines[] = $link($post['title'], $post['path'].'.md', $post['description']);
        }
        $lines[] = '';
        $lines[] = '## About';
        $lines[] = $link('About '.config('app.name'), '/about');
        $lines[] = $link('Contact', '/contact');
        $lines[] = $link('Sitemap', '/sitemap.xml');
        return $this->text(implode("\n", $lines)."\n", 'text/markdown');
    }

    public function manifest()
    {
        $brand = config('site.brand');
        return response()->json([
            'name' => config('app.name'),
            'short_name' => config('app.name'),
            'description' => 'IT internships and jobs worldwide.',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => $brand['background_color'],
            'theme_color' => $brand['theme_color'],
            'icons' => [
                ['src' => $brand['icon_small'], 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $brand['icon'], 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function indexNowKey()
    {
        abort_unless(strlen((string) config('services.indexnow.key')) >= 8, 404);
        return $this->text(config('services.indexnow.key'));
    }

    private function text(string $body, string $type = 'text/plain')
    {
        return response($body, 200)->header('Content-Type', $type.'; charset=UTF-8');
    }
}
