@extends('layouts.storefront')

@section('title', $product->meta_title ?: $product->title)
@section('meta_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 155))
@section('canonical', $isPreview ? request()->url() : route('products.show', ['product' => $product->slug]))
@section('robots', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('og_title', $product->meta_title ?: $product->title)
@section('og_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 155))
@section('og_type', 'product')
@if ($product->images->isNotEmpty())
    @section('og_image', Storage::disk($product->images->first()->disk)->url($product->images->first()->path))
@endif

@push('structured-data')
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">@json($bookStructuredData)</script>
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">@json($breadcrumbStructuredData)</script>
@endpush

@section('content')
    <div class="page-container py-8 md:py-12">
        @if ($isPreview)
            <aside class="mb-6 border border-gold bg-ivory p-4 text-sm text-brand-dark" role="status">Pratinjau admin — halaman ini hanya dapat diakses admin dan tidak menerima pesanan.</aside>
        @endif
        <nav class="mb-6 text-sm text-ink/60" aria-label="Breadcrumb">
            <ol class="flex flex-wrap gap-2">
                <li><a href="{{ route('home') }}">Beranda</a></li><li aria-hidden="true">/</li>
                <li><a href="{{ route('products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li><li aria-hidden="true">/</li>
                <li aria-current="page">{{ $product->title }}</li>
            </ol>
        </nav>

        <article>
            <div class="grid gap-8 lg:grid-cols-[minmax(260px,0.75fr)_minmax(0,1.25fr)] lg:gap-14">
                <div>
                    @if ($product->images->isNotEmpty())
                        @php
                            $primaryImage = $product->images->first();
                            $primaryImageSrcSet = collect($primaryImage->variants ?? [])->map(
                                fn ($path, $width) => Storage::disk($primaryImage->disk)->url($path).' '.$width.'w'
                            )->implode(', ');
                        @endphp
                        <div class="product-gallery" data-product-gallery>
                            <div class="product-gallery__stage">
                                <img
                                    class="product-gallery__main-image"
                                    data-product-gallery-main
                                    src="{{ Storage::disk($primaryImage->disk)->url($primaryImage->variants[1200] ?? $primaryImage->path) }}"
                                    @if ($primaryImageSrcSet !== '') srcset="{{ $primaryImageSrcSet }}" @endif
                                    sizes="(max-width: 1023px) 90vw, 42vw"
                                    width="{{ $primaryImage->width }}"
                                    height="{{ $primaryImage->height }}"
                                    alt="{{ $primaryImage->alt_text }}"
                                    fetchpriority="high"
                                    decoding="async"
                                >
                                @if ($product->images->count() > 1)
                                    <span class="product-gallery__count">{{ $product->images->count() }} foto</span>
                                @endif
                            </div>

                            @if ($product->images->count() > 1)
                                <div class="product-gallery__thumbnails" role="group" aria-label="Pilih foto produk">
                                    @foreach ($product->images as $index => $image)
                                        @php
                                            $imageSrcSet = collect($image->variants ?? [])->map(
                                                fn ($path, $width) => Storage::disk($image->disk)->url($path).' '.$width.'w'
                                            )->implode(', ');
                                        @endphp
                                        <button
                                            class="product-gallery__thumbnail"
                                            type="button"
                                            data-product-gallery-thumb
                                            data-src="{{ Storage::disk($image->disk)->url($image->variants[1200] ?? $image->path) }}"
                                            data-srcset="{{ $imageSrcSet }}"
                                            data-alt="{{ $image->alt_text }}"
                                            data-width="{{ $image->width }}"
                                            data-height="{{ $image->height }}"
                                            aria-label="Tampilkan foto {{ $index + 1 }}: {{ $image->alt_text }}"
                                            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                                        >
                                            <img
                                                src="{{ Storage::disk($image->disk)->url($image->variants[320] ?? $image->path) }}"
                                                width="{{ $image->width }}"
                                                height="{{ $image->height }}"
                                                alt=""
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex aspect-[2/3] items-center justify-center border border-rule bg-white p-6 text-center text-sm text-ink/60">Sampul belum tersedia.</div>
                    @endif
                </div>

                <div>
                    <p class="text-sm text-brand">{{ $product->category->name }}</p>
                    <h1 class="mt-2 font-display text-3xl leading-tight text-brand-dark md:text-4xl">{{ $product->title }}</h1>
                    <p class="mt-3 text-sm text-ink/70">{{ $product->author }} · {{ $product->publisher }}</p>
                    @if ($product->isbn)<p class="mt-2 text-xs text-ink/60">ISBN {{ $product->isbn }}</p>@endif

                    <div class="mt-6 border-y border-rule py-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink/60">Harga satuan</p>
                        <p class="mt-1 text-2xl font-semibold text-brand-dark">Rp{{ number_format($product->unit_price, 0, ',', '.') }} <span class="text-sm font-normal">/ buku</span></p>
                        @if ($product->wholesale_price !== null && $product->wholesale_minimum !== null)
                            <div class="mt-4 border-t border-rule pt-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-ink/60">Harga grosir</p>
                                <p class="mt-1 text-lg font-semibold text-brand-dark">Rp{{ number_format($product->wholesale_price, 0, ',', '.') }} <span class="text-sm font-normal">/ buku</span></p>
                                <p class="mt-1 text-sm text-ink/70">Berlaku mulai {{ number_format($product->wholesale_minimum, 0, ',', '.') }} buku.</p>
                            </div>
                        @endif
                        @if ((int) $product->stock === 0)
                            <p class="mt-3 text-sm text-red-800">Stok habis · tanyakan ketersediaan</p>
                        @else
                            <p class="mt-3 text-sm text-ink/70">Stok tercatat: {{ number_format($product->stock, 0, ',', '.') }}</p>
                        @endif
                    </div>

                    @if ($isPreview)
                        <p class="mt-5 border border-rule bg-ivory p-3 text-sm text-ink/70">Tindakan WhatsApp dinonaktifkan dalam pratinjau.</p>
                    @elseif ($whatsappAvailable)
                        <form class="mt-5 flex flex-wrap items-end gap-3" method="GET" action="{{ route('products.whatsapp', ['product' => $product->slug]) }}">
                            <div>
                                <label class="block text-sm font-medium" for="quantity">Jumlah</label>
                                <input class="mt-1 w-28 border border-rule bg-white px-3 py-2" id="quantity" name="quantity" type="number" min="1" step="1" value="1" required>
                            </div>
                            <button class="bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark" type="submit">Tanyakan buku via WhatsApp</button>
                        </form>
                    @else
                        <button class="mt-5 cursor-not-allowed border border-rule px-5 py-2.5 text-sm text-ink/50" type="button" disabled aria-disabled="true">WhatsApp belum tersedia</button>
                    @endif

                    <section class="mt-8" aria-labelledby="description-title">
                        <h2 class="font-display text-2xl text-brand-dark" id="description-title">Deskripsi</h2>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-ink/80">{{ $product->description }}</p>
                    </section>

                    @if (is_array($product->specifications) && $product->specifications !== [])
                        <section class="mt-8" aria-labelledby="specifications-title">
                            <h2 class="font-display text-2xl text-brand-dark" id="specifications-title">Spesifikasi</h2>
                            <dl class="mt-3 divide-y divide-rule border-y border-rule text-sm">
                                @foreach ($product->specifications as $label => $value)
                                    @if (is_scalar($value))
                                        <div class="grid grid-cols-2 gap-4 py-3"><dt class="text-ink/60">{{ is_string($label) ? $label : 'Spesifikasi' }}</dt><dd class="text-right">{{ $value }}</dd></div>
                                    @endif
                                @endforeach
                            </dl>
                        </section>
                    @endif
                </div>
            </div>
        </article>

        @if ($relatedProducts->isNotEmpty())
            <section class="py-12 md:py-16" aria-labelledby="related-products-title">
                <div class="section-heading"><h2 id="related-products-title">Buku dalam kategori ini</h2></div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 md:gap-6 xl:grid-cols-4">
                    @foreach ($relatedProducts as $relatedProduct)<x-book-card :product="$relatedProduct" />@endforeach
                </div>
            </section>
        @endif
    </div>
@endsection