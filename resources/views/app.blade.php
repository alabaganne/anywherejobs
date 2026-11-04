<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ($page['props']['locale'] ?? app()->getLocale())) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ config('site.brand.logo') }}" type="image/svg+xml">
        <link rel="icon" href="{{ config('site.brand.icon') }}" type="image/png" sizes="512x512">
        <link rel="apple-touch-icon" href="{{ config('site.brand.apple_touch_icon') }}">
        <meta name="theme-color" content="{{ config('site.brand.theme_color') }}">
        <link rel="manifest" href="/site.webmanifest">
        @php($blogLocale = ($page['props']['locale'] ?? 'en') === 'fr' ? 'fr' : 'en')
        <link rel="alternate" type="application/rss+xml" title="{{ $blogLocale === 'fr' ? 'Guides carrière '.config('app.name') : config('app.name').' career guides' }}" href="{{ $blogLocale === 'fr' ? '/fr/blog/feed.xml' : '/blog/feed.xml' }}">

        <!-- Scripts -->
        @inertiaHead
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
