<section class="py-8 md:py-14" aria-labelledby="latest-articles-title">
    <div class="section-heading flex flex-wrap items-end justify-between gap-4">
        <div><h2 id="latest-articles-title">Artikel terbaru</h2></div>
        @if ($articles->isNotEmpty())<a class="text-sm text-brand underline underline-offset-4" href="{{ route('articles.index') }}">Lihat semua artikel</a>@endif
    </div>
    @if ($articles->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($articles as $article)
                <article class="border border-rule bg-white p-5">
                    @if ($article->image_path)<img class="mb-4 aspect-[3/2] w-full object-cover" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($article->image_path) }}" alt="{{ $article->image_alt ?? '' }}" width="640" height="426" loading="lazy">@endif
                    @if ($article->published_at)<p class="text-xs text-ink/60">{{ $article->published_at->translatedFormat('d F Y') }}</p>@endif
                    <h3 class="mt-2 font-display text-xl text-brand-dark"><a href="{{ route('articles.show', ['article' => $article->slug]) }}">{{ $article->title }}</a></h3>
                    <p class="mt-2 line-clamp-3 text-sm text-ink/70">{{ $article->summary }}</p>
                </article>
            @endforeach
        </div>
    @else
        <p class="border border-rule bg-white p-5 text-sm text-ink/70">Belum ada artikel terbit.</p>
    @endif
</section>
