@extends('layouts.storefront')

@section('title', 'Katalog Buku')
@section('meta_description', 'Cari dan jelajahi katalog buku '.$siteName.'.')
@section('canonical', route('products.index'))
@section('robots', request()->query() ? 'noindex,follow' : 'index,follow')
@section('og_title', 'Katalog Buku | '.$siteName)
@section('og_description', 'Cari dan jelajahi katalog buku '.$siteName.'.')

@push('structured-data')
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">@json($breadcrumbStructuredData)</script>
@endpush

@section('content')
    <div class="page-container py-8 md:py-12">
        <nav class="mb-5 text-sm text-ink/60" aria-label="Breadcrumb">
            <ol class="flex flex-wrap gap-2"><li><a href="{{ route('home') }}">Beranda</a></li><li aria-hidden="true">/</li><li aria-current="page">Produk</li></ol>
        </nav>
        <div class="section-heading">
            <p class="text-sm text-brand">Toko Buku Alfikar</p>
            <h1 class="font-display text-3xl text-brand-dark">Katalog buku</h1>
        </div>

        <form class="mb-8 grid gap-3 border border-rule bg-white p-4 md:grid-cols-[minmax(0,1fr)_minmax(180px,0.6fr)_minmax(180px,0.6fr)_auto] md:items-end" method="GET" action="{{ route('products.index') }}">
            <div>
                <label class="block text-sm font-medium" for="catalog-q">Cari judul, penulis, penerbit, ISBN, atau kode</label>
                <input class="mt-1 w-full border border-rule px-3 py-2" id="catalog-q" name="q" type="search" maxlength="120" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div>
                <label class="block text-sm font-medium" for="catalog-category">Kategori</label>
                <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="catalog-category" name="category">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium" for="catalog-sort">Urutkan</label>
                <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="catalog-sort" name="sort">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Terbaru</option>
                    <option value="title" @selected(($filters['sort'] ?? '') === 'title')>Judul A–Z</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Harga terendah</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Harga tertinggi</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="bg-brand px-5 py-2 text-sm text-white hover:bg-brand-dark" type="submit">Cari buku</button>
                @if ($filters !== [])
                    <a class="inline-flex items-center text-sm text-brand-dark underline underline-offset-4" href="{{ route('products.index') }}">Hapus filter</a>
                @endif
            </div>
        </form>

        <p class="mb-5 text-sm text-ink/60">{{ $products->total() }} buku</p>
        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 md:gap-6 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-book-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-10">{{ $products->links() }}</div>
        @else
            <div class="border border-rule bg-white px-5 py-10 text-center">
                <h2 class="font-display text-xl text-brand-dark">Buku tidak ditemukan</h2>
                <p class="mt-2 text-sm text-ink/70">
                    @if (filled($filters['q'] ?? null))
                        Tidak ada buku yang cocok dengan pencarian “{{ $filters['q'] }}”. Coba istilah yang lebih singkat atau hapus filter.
                    @else
                        Belum ada buku yang cocok dengan pilihan ini. Silakan ubah kategori atau urutan.
                    @endif
                </p>
                @if ($filters !== [])
                    <a class="mt-4 inline-flex border border-brand px-4 py-2 text-sm text-brand-dark" href="{{ route('products.index') }}">Lihat seluruh katalog</a>
                @endif
            </div>
        @endif
    </div>
@endsection