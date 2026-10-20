<section class="py-8 md:py-14" aria-labelledby="categories-title">
    <div class="section-heading"><h2 id="categories-title">Kategori</h2></div>
    @if ($categories->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($categories as $category)
                <a class="category-card group overflow-hidden border border-rule bg-white transition-colors hover:border-brand" href="{{ route('products.index', ['category' => $category->slug]) }}">
                    @php($categoryCover = $category->products->first()?->images->first())
                    @if ($category->image_path)
                        <img class="aspect-square w-full object-cover" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($category->image_path) }}" alt="" width="640" height="640" loading="lazy">
                    @elseif ($categoryCover)
                        <img class="aspect-square w-full bg-ivory object-contain p-4" src="{{ Storage::disk($categoryCover->disk)->url($categoryCover->variants[320] ?? $categoryCover->path) }}" alt="" width="640" height="640" loading="lazy">
                    @else
                        <span class="flex aspect-square w-full items-center justify-center bg-ivory px-6 text-center font-display text-xl text-brand-dark">{{ $category->name }}</span>
                    @endif
                    <span class="flex min-h-24 items-end justify-between gap-3 p-5">
                        <span class="font-display text-xl text-brand-dark">{{ $category->name }}</span>
                        <span class="shrink-0 text-sm text-ink/60">{{ $category->published_products_count }} buku</span>
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <p class="border border-rule bg-white p-5 text-sm text-ink/70">Kategori belum tersedia.</p>
    @endif
</section>
