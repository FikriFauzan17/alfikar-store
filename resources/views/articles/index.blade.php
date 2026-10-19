@extends('layouts.storefront')

@section('title', 'Artikel'.($articles->currentPage() > 1 ? ' - Halaman '.$articles->currentPage() : ''))
@section('meta_description', 'Artikel terbaru dari '.$siteName.($articles->currentPage() > 1 ? ', halaman '.$articles->currentPage().'.' : '.'))
@section('canonical', $articleListUrl)
@section('robots', $robots)
@section('og_title', 'Artikel'.($articles->currentPage() > 1 ? ' - Halaman '.$articles->currentPage() : '').' | '.$siteName)
@section('og_description', 'Artikel terbaru dari '.$siteName.($articles->currentPage() > 1 ? ', halaman '.$articles->currentPage().'.' : '.'))

@push('structured-data')
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">@json($breadcrumbStructuredData)</script>
@endpush

@section('content')
    <div class="page-container py-8 md:py-12">
        <nav class="mb-5 text-sm text-ink/60" aria-label="Breadcrumb"><ol class="flex gap-2"><li><a href="{{ route('home') }}">Beranda</a></li><li aria-hidden="true">/</li><li aria-current="page">Artikel</li></ol></nav>
        <div class="section-heading"><p class="text-sm text-brand">{{ $siteName }}</p><h1 class="font-display text-3xl text-brand-dark">Artikel</h1></div>
        @if ($articles->isNotEmpty())
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($articles as $article)
                    <article class="border border-rule bg-white p-5">
                        @if ($article->image_path)
                            <img class="mb-4 aspect-[3/2] w-full object-cover" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($article->image_path) }}" alt="{{ $article->image_alt ?? '' }}" width="640" height="426" loading="lazy">
                        @endif
                        @if ($article->published_at)<p class="text-xs text-ink/60">{{ $article->published_at->translatedFormat('d F Y') }}</p>@endif
                        <h2 class="mt-2 font-display text-xl text-brand-dark"><a href="{{ route('articles.show', ['article' => $article->slug]) }}">{{ $article->title }}</a></h2>
                        <p class="mt-2 line-clamp-4 text-sm text-ink/70">{{ $article->summary }}</p>
                        @if ($article->author)<p class="mt-4 text-xs text-ink/60">{{ $article->author->name }}</p>@endif
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $articles->links() }}</div>
        @else
            <p class="border border-rule bg-white p-5 text-sm text-ink/70">Belum ada artikel terbit.</p>
        @endif
    </div>
@endsection