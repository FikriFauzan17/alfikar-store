@props(['siteName' => 'Toko Buku Alfikar', 'siteLogoUrl' => null, 'informationPages' => []])

<header class="site-header">
    <a class="site-brand" href="{{ route('home') }}" aria-label="{{ $siteName }}, beranda">
        @if ($siteLogoUrl)
            <img class="site-brand__logo" src="{{ $siteLogoUrl }}" alt="Logo {{ $siteName }}">
        @endif
        <span class="site-brand__text">
            <span class="site-brand__name">{{ $siteName }}</span>
            <span class="site-brand__descriptor">Toko Buku</span>
        </span>
    </a>

    <details class="site-menu">
        <summary class="site-menu__trigger">Menu</summary>
        <nav class="site-nav" aria-label="Navigasi utama">
            <a href="{{ route('home') }}">Beranda</a>
            <a href="{{ route('products.index') }}">Produk</a>
            <a href="{{ route('pages.about') }}">Tentang</a>
            <a href="{{ route('articles.index') }}">Artikel</a>
            <a href="{{ route('pages.contact') }}">Kontak</a>
            @foreach ($informationPages as $slug => $title)
                <a href="{{ route('pages.information', ['page' => $slug]) }}">{{ $title }}</a>
            @endforeach
        </nav>
    </details>
    <a class="site-header__action" href="{{ route('products.index') }}">Lihat produk</a>
</header>