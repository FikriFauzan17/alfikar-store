<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('meta_description', $siteSeo['seo_description'] ?? 'Profil dan katalog Toko Buku Alfikar.')">
        <meta name="theme-color" content="#FAF9F6">
        @if ($siteLogoUrl)
            <link rel="icon" href="{{ $siteLogoUrl }}" type="image/webp">
        @endif
        <title>@yield('title', $siteSeo['seo_title'] ?? 'Beranda') | {{ $siteName }}</title>
        <link rel="canonical" href="@yield('canonical', request()->url())">
        <meta name="robots" content="@yield('robots', 'index,follow')">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:type" content="@yield('og_type', 'website')">
        <meta property="og:title" content="@yield('og_title', $siteSeo['seo_title'] ?? $siteName)">
        <meta property="og:description" content="@yield('og_description', $siteSeo['seo_description'] ?? 'Profil dan katalog Toko Buku Alfikar.')">
        @hasSection('og_image')
            <meta property="og:image" content="@yield('og_image')">
        @endif
        @stack('structured-data')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="site-shell">
            <x-site.header :site-name="$siteName" :site-logo-url="$siteLogoUrl" :information-pages="$informationPages" />
            <div class="site-header-spacer" aria-hidden="true"></div>

            <main class="site-main">
                @yield('content')
            </main>

            <x-site.footer :site-name="$siteName" :information-pages="$informationPages" />
        </div>
    </body>
</html>