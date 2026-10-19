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

<section class="home-intro {{ $featuredProduct?->images->isNotEmpty() ? '' : 'home-intro--empty' }}" aria-labelledby="home-title">
    <div class="home-intro__copy">
        <p class="home-intro__eyebrow">{{ $siteName }}</p>
        @if ($featuredProduct)
            <p class="mb-3 text-sm text-gold">Buku unggulan</p>
            <h1 id="home-title">{{ filled($homeCopy['home_hero_title'] ?? null) ? $homeCopy['home_hero_title'] : $featuredProduct->title }}</h1>
            <p class="home-intro__description">{{ filled($homeCopy['home_hero_description'] ?? null) ? $homeCopy['home_hero_description'] : $featuredProduct->author.' · '.$featuredProduct->publisher }}</p>
            @php
                $heroCtaUrl = filled($homeCopy['home_hero_cta_url'] ?? null)
                    ? $homeCopy['home_hero_cta_url']
                    : route('products.show', ['product' => $featuredProduct->slug]);
                $heroCtaLabel = filled($homeCopy['home_hero_cta_label'] ?? null)
                    ? $homeCopy['home_hero_cta_label']
                    : 'Lihat detail buku';
            @endphp
            <a class="mt-6 inline-flex border border-gold px-5 py-3 text-sm text-white hover:bg-white/10" href="{{ $heroCtaUrl }}" @if (str_starts_with($heroCtaUrl, 'https://')) target="_blank" rel="noopener noreferrer" @endif>{{ $heroCtaLabel }}</a>
        @else
            <h1 id="home-title">{{ filled($homeCopy['home_hero_title'] ?? null) ? $homeCopy['home_hero_title'] : 'Buku untuk menemani pencarian ilmu.' }}</h1>
            <p class="home-intro__description">{{ filled($homeCopy['home_hero_description'] ?? null) ? $homeCopy['home_hero_description'] : 'Belum ada buku unggulan yang diterbitkan.' }}</p>
            @php
                $heroCtaUrl = filled($homeCopy['home_hero_cta_url'] ?? null) ? $homeCopy['home_hero_cta_url'] : route('products.index');
                $heroCtaLabel = filled($homeCopy['home_hero_cta_label'] ?? null) ? $homeCopy['home_hero_cta_label'] : 'Jelajahi katalog';
            @endphp
            <a class="mt-6 inline-flex border border-gold px-5 py-3 text-sm text-white hover:bg-white/10" href="{{ $heroCtaUrl }}" @if (str_starts_with($heroCtaUrl, 'https://')) target="_blank" rel="noopener noreferrer" @endif>{{ $heroCtaLabel }}</a>
        @endif
    </div>
    @if ($featuredProduct?->images->isNotEmpty())
        @php
            $heroImage = $featuredProduct->images->first();
            $heroSrcSet = collect($heroImage->variants ?? [])->map(
                fn ($path, $width) => Storage::disk($heroImage->disk)->url($path).' '.$width.'w'
            )->implode(', ');
        @endphp
        <a class="home-intro__cover" href="{{ route('products.show', ['product' => $featuredProduct->slug]) }}" aria-label="Lihat {{ $featuredProduct->title }}">
            <img src="{{ Storage::disk($heroImage->disk)->url($heroImage->variants[640] ?? $heroImage->path) }}" srcset="{{ $heroSrcSet }}" sizes="(max-width: 700px) 34vw, 320px" width="{{ $heroImage->width }}" height="{{ $heroImage->height }}" alt="{{ $heroImage->alt_text }}" fetchpriority="high">
        </a>
    @endif
</section>
