@extends('layouts.storefront')

@section('title', $article->meta_title ?: $article->title)
@section('meta_description', $article->meta_description ?: \Illuminate\Support\Str::limit($article->summary, 155))
@section('canonical', $isPreview ? request()->url() : route('articles.show', ['article' => $article->slug]))
@section('robots', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('og_title', $article->meta_title ?: $article->title)
@section('og_description', $article->meta_description ?: \Illuminate\Support\Str::limit($article->summary, 155))
@section('og_type', 'article')
@if ($article->image_path)
    @section('og_image', Storage::disk(config('image-processing.disk', 'public'))->url($article->image_path))
@endif

@push('structured-data')
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">@json($articleStructuredData)</script>
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">@json($breadcrumbStructuredData)</script>
@endpush

@section('content')
    <div class="page-container max-w-4xl py-8 md:py-12">
        @if ($isPreview)
            <aside class="mb-6 border border-gold bg-ivory p-4 text-sm text-brand-dark" role="status">Pratinjau admin — halaman ini hanya dapat diakses admin dan tidak diindeks.</aside>
        @endif
        <nav class="mb-6 text-sm text-ink/60" aria-label="Breadcrumb"><ol class="flex gap-2"><li><a href="{{ route('home') }}">Beranda</a></li><li aria-hidden="true">/</li><li><a href="{{ route('articles.index') }}">Artikel</a></li><li aria-hidden="true">/</li><li aria-current="page">{{ $article->title }}</li></ol></nav>
        <article>
            <p class="text-sm text-brand">Artikel {{ $siteName }}</p>
            <h1 class="mt-2 font-display text-3xl leading-tight text-brand-dark md:text-4xl">{{ $article->title }}</h1>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink/60">
                @if ($article->published_at)<time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->translatedFormat('d F Y') }}</time>@endif
                @if ($article->author)<span>{{ $article->author->name }}</span>@endif
            </div>
            @if ($article->image_path)
                <img class="mt-8 aspect-[3/2] w-full object-cover" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($article->image_path) }}" alt="{{ $article->image_alt ?? '' }}" width="1200" height="800" fetchpriority="high">
            @endif
            <p class="mt-8 border-l-2 border-gold pl-5 text-lg text-ink/80">{{ $article->summary }}</p>
            <div class="article-content mt-8 text-base leading-8 text-ink/85">{!! $safeBody !!}</div>
        </article>
    </div>
@endsection