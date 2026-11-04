<?php

namespace App\Console\Commands;

use App\Support\CvPdf;
use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

class BrandImages extends Command
{
    protected $signature = 'brand:images';
    protected $description = 'Rebuild the logo PNGs and the share image from the logo SVG and the site name';

    public function handle(): int
    {
        $brand = config('site.brand');
        $logo = public_path(ltrim($brand['logo'], '/'));
        if (! is_file($logo)) {
            $this->error("No logo at {$logo}.");
            return self::FAILURE;
        }
        $data = fn (string $path, string $type) => 'data:'.$type.';base64,'.base64_encode(file_get_contents($path));
        $svg = $data($logo, 'image/svg+xml');

        // Browser and app icons keep the logo's own shape; the Apple icon needs a solid background.
        foreach ([[$brand['icon'], 512, null], [$brand['icon_small'], 192, null], [$brand['apple_touch_icon'], 180, '#ffffff']] as [$path, $size, $background]) {
            $html = '<!doctype html><html><body style="margin:0;background:'.($background ?? 'transparent').'"><img src="'.$svg.'" width="'.$size.'" height="'.$size.'" style="display:block"></body></html>';
            $this->shoot($html, $size, $size, $path, $background === null);
        }

        $html = view('brand.og', [
            'name' => config('app.name'),
            'logo' => $svg,
            'mascot' => $data(public_path('site-assets/mascots/07-search-internships.svg'), 'image/svg+xml'),
            'display' => $data(resource_path('fonts/poppins-600-latin.woff2'), 'font/woff2'),
            'body' => $data(resource_path('fonts/poppins-400-latin.woff2'), 'font/woff2'),
        ])->render();
        $this->shoot($html, 1200, 630, $brand['og_image'], false);

        $this->info('Rebuilt '.implode(', ', [$brand['icon'], $brand['icon_small'], $brand['apple_touch_icon'], $brand['og_image']]).'.');
        return self::SUCCESS;
    }

    private function shoot(string $html, int $width, int $height, string $path, bool $transparent): void
    {
        $shot = CvPdf::browser(Browsershot::html($html))->windowSize($width, $height);
        $transparent ? $shot->hideBackground() : $shot->showBackground();
        $shot->save(public_path(ltrim($path, '/')));
    }
}
