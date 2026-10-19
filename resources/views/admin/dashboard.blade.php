@extends('layouts.admin')

@section('title', 'Ringkasan')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Administrasi</p>
            <h1 class="font-display text-3xl text-brand-dark">Ringkasan Alfikar</h1>
            <p class="admin-page-heading__description">Selamat datang, {{ auth()->user()->name }}. Berikut kondisi katalog dan konten saat ini.</p>
        </div>
    </div>

    <section class="admin-stat-grid" aria-label="Ringkasan data">
        <a class="admin-stat-card" href="{{ route('admin.products.index') }}">
            <span class="admin-stat-card__label">Produk terbit</span>
            <strong data-stat="published-products">{{ number_format($statistics['publishedProducts']) }}</strong>
            <span class="admin-stat-card__note">Tampil di katalog</span>
        </a>
        <a class="admin-stat-card" href="{{ route('admin.products.index') }}">
            <span class="admin-stat-card__label">Stok habis</span>
            <strong data-stat="out-of-stock-products">{{ number_format($statistics['outOfStockProducts']) }}</strong>
            <span class="admin-stat-card__note">Semua produk dengan stok nol</span>
        </a>
        <a class="admin-stat-card" href="{{ route('admin.articles.index') }}">
            <span class="admin-stat-card__label">Artikel</span>
            <strong data-stat="articles">{{ number_format($statistics['articles']) }}</strong>
            <span class="admin-stat-card__note">Semua status artikel</span>
        </a>
        @can('access-admin')
            <a class="admin-stat-card" href="{{ route('admin.users.index') }}">
                <span class="admin-stat-card__label">Pengguna</span>
                <strong data-stat="users">{{ number_format($statistics['users']) }}</strong>
                <span class="admin-stat-card__note">Kelola akses akun</span>
            </a>
        @endcan
    </section>

    <section class="admin-section" aria-labelledby="admin-shortcuts-title">
        <div class="admin-section__heading">
            <div>
                <p class="admin-eyebrow">Akses cepat</p>
                <h2 id="admin-shortcuts-title">Kelola toko</h2>
            </div>
        </div>
        <div class="admin-shortcut-grid">
            <a class="admin-shortcut-card" href="{{ route('admin.products.index') }}">
                <span class="admin-shortcut-card__icon" aria-hidden="true">01</span>
                <span><strong>Produk</strong><small>Data buku, harga, stok, dan foto</small></span>
                <span aria-hidden="true">→</span>
            </a>
            <a class="admin-shortcut-card" href="{{ route('admin.categories.index') }}">
                <span class="admin-shortcut-card__icon" aria-hidden="true">02</span>
                <span><strong>Kategori</strong><small>Pengelompokan katalog</small></span>
                <span aria-hidden="true">→</span>
            </a>
            <a class="admin-shortcut-card" href="{{ route('admin.articles.index') }}">
                <span class="admin-shortcut-card__icon" aria-hidden="true">03</span>
                <span><strong>Artikel</strong><small>Draft, jadwal, dan publikasi</small></span>
                <span aria-hidden="true">→</span>
            </a>
            <a class="admin-shortcut-card" href="{{ route('admin.banners.index') }}">
                <span class="admin-shortcut-card__icon" aria-hidden="true">04</span>
                <span><strong>Banner</strong><small>Konten unggulan beranda</small></span>
                <span aria-hidden="true">→</span>
            </a>
            <a class="admin-shortcut-card" href="{{ route('admin.settings.edit') }}">
                <span class="admin-shortcut-card__icon" aria-hidden="true">05</span>
                <span><strong>Pengaturan situs</strong><small>Profil, kontak, dan SEO</small></span>
                <span aria-hidden="true">→</span>
            </a>
            @can('access-admin')
                <a class="admin-shortcut-card" href="{{ route('admin.users.index') }}">
                    <span class="admin-shortcut-card__icon" aria-hidden="true">06</span>
                    <span><strong>Pengguna</strong><small>Buat akun dan atur hak akses</small></span>
                    <span aria-hidden="true">→</span>
                </a>
            @endcan
        </div>
    </section>
@endsection