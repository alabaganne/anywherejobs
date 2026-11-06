<?php

namespace App\Console\Commands;

use App\Support\CvPdf;
use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

class BrandImages extends Command
{
    protected $signature = 'brand:images';
    protected $description = 'Rebuild the logo PNGs, favicon.ico and the share image from the logo SVGs and the site name';

    public function handle(): int
    {
        $brand = config('site.brand');
        $files = [];
        foreach (['logo', 'wordmark', 'wordmark_white'] as $key) {
            $files[$key] = public_path(ltrim($brand[$key], '/'));
            if (! is_file($files[$key])) {
                $this->error("No {$key} at {$files[$key]}.");
                return self::FAILURE;
            }
        }
        $data = fn (string $path, string $type) => 'data:'.$type.';base64,'.base64_encode(file_get_contents($path));
        $svg = $data($files['logo'], 'image/svg+xml');
        $image = fn (string $src, int $width, int $height, string $background = 'transparent') => '<!doctype html><html><body style="margin:0;background:'.$background.'"><img src="'.$src.'" width="'.$width.'" height="'.$height.'" style="display:block"></body></html>';

        // Browser and app icons keep the logo's own shape; the Apple icon needs a solid background.
        foreach ([[$brand['icon'], 512, null], [$brand['icon_small'], 192, null], [$brand['apple_touch_icon'], 180, '#ffffff']] as [$path, $size, $background]) {
            $this->shoot($image($svg, $size, $size, $background ?? 'transparent'), $size, $size, $this->public($path), $background === null);
        }

        // Emails show the wordmark at 40px high, so it is drawn three times larger for sharp screens.
        $this->shoot($image($data($files['wordmark'], 'image/svg+xml'), 574, 120), 574, 120, $this->public($brand['wordmark_png']), true);

        $pngs = [];
        foreach ([16, 32, 48] as $size) {
            $tmp = storage_path("app/favicon-{$size}.png");
            $this->shoot($image($svg, $size, $size), $size, $size, $tmp, true);
            $pngs[$size] = file_get_contents($tmp);
            @unlink($tmp);
        }
        file_put_contents(public_path('favicon.ico'), $this->ico($pngs));

        $html = view('brand.og', [
            'name' => config('app.name'),
            'wordmark' => $data($files['wordmark_white'], 'image/svg+xml'),
            'mascot' => $data(public_path('site-assets/mascots/07-search-internships.svg'), 'image/svg+xml'),
            'display' => $data(resource_path('fonts/poppins-600-latin.woff2'), 'font/woff2'),
            'body' => $data(resource_path('fonts/poppins-400-latin.woff2'), 'font/woff2'),
        ])->render();
        $this->shoot($html, 1200, 630, $this->public($brand['og_image']), false);

        $this->info('Rebuilt '.implode(', ', [$brand['icon'], $brand['icon_small'], $brand['apple_touch_icon'], $brand['wordmark_png'], '/favicon.ico', $brand['og_image']]).'.');
        return self::SUCCESS;
    }

    private function public(string $path): string
    {
        return public_path(ltrim($path, '/'));
    }

    private function shoot(string $html, int $width, int $height, string $file, bool $transparent): void
    {
        $shot = CvPdf::browser(Browsershot::html($html))->windowSize($width, $height);
        $transparent ? $shot->hideBackground() : $shot->showBackground();
        $shot->save($file);
    }

    // An ICO file that holds PNG images, which every current browser reads.
    private function ico(array $pngs): string
    {
        $offset = 6 + 16 * count($pngs);
        $entries = $images = '';
        foreach ($pngs as $size => $png) {
            $entries .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), $offset);
            $images .= $png;
            $offset += strlen($png);
        }
        return pack('vvv', 0, 1, count($pngs)).$entries.$images;
    }
}
