# Logo and name

## Change the name

The name is set once, in `config/app.php` (`'name' => env('APP_NAME', ...)`). An `APP_NAME` line in `.env` overrides it. Pages, page titles, emails, the web manifest, structured data, the AI instructions and the blog (which writes `{site}` where the name goes) all read it from there. After changing it, run `php artisan brand:images` so the share image shows the new name.

## Change the logo

1. Replace `logo.svg` in this folder (square, ideally a 64 × 64 viewBox).
2. Run `php artisan brand:images`. It rebuilds the PNG copies below from the SVG, and the share image from the SVG and the name.

The file paths and colours are in `config/site.php` under `brand`. `resources/js/Shared/BrandLogo.vue` shows the logo everywhere on the site.

## Files

- `logo.svg`: the logo, a terminal window with a `>_` prompt in white on a cobalt tile. The site shows it through `BrandLogo` and uses it as the browser icon.
- `symbol.png` (512 × 512) and `symbol-192.png`: PNG copies for the web manifest, Google and emails, which can't show SVG.
- `apple-touch-icon.png` (180 × 180): the logo on a white background for iPhone home screens.
- `og-default.png` (1200 × 630): the share image used when a page has none of its own. Its layout is `resources/views/brand/og.blade.php`.

Old links under `/brand/nextward/` redirect here.
