@props(['product'])

@php
    $cover = $product->images->first();
    $srcSet = $cover
        ? collect($cover->variants ?? [])->map(fn ($path, $width) => Storage::disk($cover->disk)->url($path).' '.$width.'w')->implode(', ')
        : '';
@endphp

<article class="book-card min-w-0">
    <a class="group block" href="{{ route('products.show', ['product' => $product->slug]) }}">
        <div class="book-card__cover aspect-[2/3] overflow-hidden border border-rule bg-ivory">
            @if ($cover)
                <img class="h-full w-full object-contain p-3 transition-transform duration-300 group-hover:scale-[1.02]" src="{{ Storage::disk($cover->disk)->url($cover->variants[320] ?? $cover->path) }}" srcset="{{ $srcSet }}" sizes="(max-width: 700px) 45vw, (max-width: 1100px) 30vw, 22vw" width="{{ $cover->width }}" height="{{ $cover->height }}" alt="{{ $cover->alt_text }}" loading="lazy">
            @else
                <div class="flex h-full items-center justify-center px-4 text-center text-xs text-ink/50">Sampul belum tersedia</div>
            @endif
            @if ($product->is_bestseller)
                <span class="book-card__badge">Bestseller</span>
            @endif
        </div>
        <p class="mt-3 text-xs text-brand">{{ $product->category->name }}</p>
        <h3 class="mt-1 line-clamp-2 font-display text-lg leading-snug text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $product->title }}</h3>
    </a>
    <p class="mt-1 line-clamp-1 text-xs text-ink/60">{{ $product->author }}</p>
    <p class="mt-2 text-sm font-semibold text-ink">Rp{{ number_format($product->unit_price, 0, ',', '.') }}</p>
    @if ($product->wholesale_price !== null && $product->wholesale_minimum !== null)
        <p class="mt-1 text-xs text-ink/60">Grosir Rp{{ number_format($product->wholesale_price, 0, ',', '.') }} · min. {{ $product->wholesale_minimum }}</p>
    @endif
    <p class="book-card__availability {{ $product->stock > 0 ? 'book-card__availability--available' : 'book-card__availability--unavailable' }}">
        <span aria-hidden="true"></span>
        {{ $product->stock > 0 ? 'Tersedia' : 'Stok habis' }}
    </p>
</article>