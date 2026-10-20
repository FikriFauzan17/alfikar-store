@if ($banner)
    <section class="border-b border-rule py-5" aria-labelledby="banner-title">
        <div class="flex flex-col gap-5 md:flex-row md:items-center">
            @if ($banner->image_path)
                <img class="aspect-[3/2] w-full object-cover md:w-1/3" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($banner->image_path) }}" alt="{{ $banner->image_alt ?? '' }}" width="640" height="426" loading="lazy">
            @endif
            <div>
                @if ($banner->eyebrow)<p class="text-sm text-brand">{{ $banner->eyebrow }}</p>@endif
                <h2 id="banner-title" class="font-display text-2xl text-brand-dark">{{ $banner->title }}</h2>
                @if ($banner->description)<p class="mt-2 whitespace-pre-line text-sm text-ink/75">{{ $banner->description }}</p>@endif
                @if ($banner->link_url && $banner->link_label)
                    <a class="mt-4 inline-flex text-sm text-brand underline underline-offset-4" href="{{ $banner->link_url }}" @if (str_starts_with($banner->link_url, 'https://')) target="_blank" rel="noopener noreferrer" @endif>{{ $banner->link_label }}</a>
                @endif
            </div>
        </div>
    </section>
@endif

<section
    class="home-intro {{ $featuredProducts->isEmpty() || $featuredProducts->every(fn ($product) => $product->images->isEmpty()) ? 'home-intro--empty' : '' }}"
    aria-labelledby="home-title"
    @if ($featuredProducts->count() > 1)
        data-featured-carousel
        aria-roledescription="carousel"
        aria-label="Buku unggulan"
    @endif
>
    @forelse ($featuredProducts as $featuredProduct)
        @php
            $isFirstFeaturedProduct = $loop->first;
            $heroImage = $featuredProduct->images->first();
            $heroSrcSet = $heroImage
                ? collect($heroImage->variants ?? [])->map(
                    fn ($path, $width) => Storage::disk($heroImage->disk)->url($path).' '.$width.'w'
                )->implode(', ')
                : '';
            $heroCtaUrl = $isFirstFeaturedProduct && filled($homeCopy['home_hero_cta_url'] ?? null)
                ? $homeCopy['home_hero_cta_url']
                : route('products.show', ['product' => $featuredProduct->slug]);
            $heroCtaLabel = $isFirstFeaturedProduct && filled($homeCopy['home_hero_cta_label'] ?? null)
                ? $homeCopy['home_hero_cta_label']
                : 'Lihat detail buku';
        @endphp
        <div
            class="home-intro__slide"
            data-featured-slide
            role="group"
            aria-roledescription="slide"
            aria-label="{{ $loop->iteration }} dari {{ $loop->count }}"@if (! $isFirstFeaturedProduct) hidden @endif
        >
            <div class="home-intro__copy">
                <p class="home-intro__eyebrow">{{ $siteName }}</p>
                <p class="home-intro__label">Buku unggulan</p>
                @if ($isFirstFeaturedProduct)
                    <h1 id="home-title">{{ filled($homeCopy['home_hero_title'] ?? null) ? $homeCopy['home_hero_title'] : $featuredProduct->title }}</h1>
                    <p class="home-intro__description">{{ filled($homeCopy['home_hero_description'] ?? null) ? $homeCopy['home_hero_description'] : $featuredProduct->author.' · '.$featuredProduct->publisher }}</p>
                @else
                    <h2>{{ $featuredProduct->title }}</h2>
                    <p class="home-intro__description">{{ $featuredProduct->author.' · '.$featuredProduct->publisher }}</p>
                @endif
                <div class="home-intro__actions">
                    <a class="home-intro__action inline-flex" href="{{ $heroCtaUrl }}" @if (str_starts_with($heroCtaUrl, 'https://')) target="_blank" rel="noopener noreferrer" @endif>
                        {{ $heroCtaLabel }}
                        <span aria-hidden="true">→</span>
                    </a>
                    <a class="home-intro__secondary" href="{{ route('products.index') }}">
                        Jelajahi katalog
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
            @if ($heroImage)
                <a class="home-intro__cover" href="{{ route('products.show', ['product' => $featuredProduct->slug]) }}" aria-label="Lihat {{ $featuredProduct->title }}">
                    <img src="{{ Storage::disk($heroImage->disk)->url($heroImage->variants[640] ?? $heroImage->path) }}" @if ($heroSrcSet !== '') srcset="{{ $heroSrcSet }}" @endif sizes="(max-width: 700px) 34vw, 320px" width="{{ $heroImage->width }}" height="{{ $heroImage->height }}" alt="{{ $heroImage->alt_text }}" @if ($isFirstFeaturedProduct) fetchpriority="high" @else loading="lazy" @endif>
                </a>
            @endif
        </div>
    @empty
        <div class="home-intro__slide">
            <div class="home-intro__copy">
                <p class="home-intro__eyebrow">{{ $siteName }}</p>
                <h1 id="home-title">{{ filled($homeCopy['home_hero_title'] ?? null) ? $homeCopy['home_hero_title'] : 'Buku untuk menemani pencarian ilmu.' }}</h1>
                <p class="home-intro__description">{{ filled($homeCopy['home_hero_description'] ?? null) ? $homeCopy['home_hero_description'] : 'Belum ada buku unggulan yang diterbitkan.' }}</p>
                @php
                    $heroCtaUrl = filled($homeCopy['home_hero_cta_url'] ?? null) ? $homeCopy['home_hero_cta_url'] : route('products.index');
                    $heroCtaLabel = filled($homeCopy['home_hero_cta_label'] ?? null) ? $homeCopy['home_hero_cta_label'] : 'Jelajahi katalog';
                @endphp
                <a class="home-intro__action mt-6 inline-flex" href="{{ $heroCtaUrl }}" @if (str_starts_with($heroCtaUrl, 'https://')) target="_blank" rel="noopener noreferrer" @endif>
                    {{ $heroCtaLabel }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    @endforelse
    @if ($featuredProducts->count() > 1)
        <div class="home-intro__controls" data-featured-controls hidden role="group" aria-label="Kontrol buku unggulan">
            <button class="home-intro__control" type="button" data-featured-previous aria-label="Buku unggulan sebelumnya">
                <span aria-hidden="true">←</span>
            </button>
            <span class="home-intro__position" data-featured-position>1 / {{ $featuredProducts->count() }}</span>
            <button class="home-intro__control" type="button" data-featured-next aria-label="Buku unggulan berikutnya">
                <span aria-hidden="true">→</span>
            </button>
            <button class="home-intro__control home-intro__rotation" type="button" data-featured-rotation aria-pressed="false">
                Jeda otomatis
            </button>
        </div>
    @endif
</section>
