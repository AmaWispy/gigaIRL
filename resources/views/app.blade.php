<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'gigaIRL') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @php
            $ogTitle = 'gigaIRL — Живи. Прокачивайся. Побеждай.';
            $ogDescription = 'Браузерная текстовая RPG, где твоя реальная жизнь питает приключения. Чем продуктивнее день — тем сильнее герой.';
            $ogImage = asset('images/og-image.png');
        @endphp

        <!-- SEO / Open Graph -->
        <meta name="description" content="{{ $ogDescription }}">
        <meta name="theme-color" content="#0d0b12">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="gigaIRL">
        <meta property="og:locale" content="ru_RU">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1536">
        <meta property="og:image:height" content="1024">
        <meta property="og:image:alt" content="gigaIRL — браузерная текстовая RPG">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
