# Logo and name

## Change the name

The name is set once, in `config/app.php` (`'name' => env('APP_NAME', ...)`). An `APP_NAME` line in `.env` overrides it. Pages, page titles, emails, the web manifest, structured data, the AI instructions and the blog (which writes `{site}` where the name goes) all read it from there.

The wordmark spells the name, so a new name needs a new wordmark (see below). Then run `php artisan brand:images`.

## Change the logo

1. Replace the SVGs in this folder: `wordmark.svg` and `wordmark-white.svg` (keep the 574 × 120 viewBox, or change `WORDMARK_RATIO` in `BrandLogo.vue`) and `logo.svg` (square, 64 × 64 viewBox).
2. Run `php artisan brand:images`. It rebuilds the PNG copies below, `public/favicon.ico` and the share image.

The file paths and colours are in `config/site.php` under `brand`. `resources/js/Shared/BrandLogo.vue` shows the logo everywhere on the site: `<BrandLogo wordmark />` for the name, with `dark` on navy or cobalt.

## How the logo was drawn

The letters are the Yellowtail font (SIL Open Font License, free to use in a logo) at size 100, turned into shapes with opentype.js and thickened with a 3.5 stroke. The swoosh is one tapered shape under the name, rising to the right, with a gap cut where it crosses the letters. The icon is a Yellowtail "A" with a small swoosh on the cobalt tile. No font is loaded on the site.

## Files

- `wordmark.svg`: the name in cobalt, for light backgrounds (footer, error page).
- `wordmark-white.svg`: the name in white, for navy and cobalt (header, sidebar, sign-in pages, share image).
- `logo.svg`: the square icon, a script A with a swoosh in white on a cobalt tile. Used as the browser icon.
- `wordmark.png` (574 × 120): the cobalt wordmark for emails, which can't show SVG.
- `symbol.png` (512 × 512) and `symbol-192.png`: PNG copies of the icon for the web manifest and Google.
- `apple-touch-icon.png` (180 × 180): the icon on a white background for iPhone home screens.
- `og-default.png` (1200 × 630): the share image used when a page has none of its own. Its layout is `resources/views/brand/og.blade.php`.

Old links under `/brand/nextward/` redirect here.
