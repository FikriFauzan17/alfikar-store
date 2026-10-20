@extends('layouts.storefront')

@section('title', $pageTitle)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($safeContent), 155, ''))
@section('robots', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('og_title', $pageTitle)
@section('og_description', \Illuminate\Support\Str::limit(strip_tags($safeContent), 155, ''))

@section('content')
    <div class="page-container py-8 md:py-12">
        <nav class="mb-5 text-sm text-ink/60" aria-label="Breadcrumb">
            <ol class="flex flex-wrap gap-2">
                <li><a href="{{ route('home') }}">Beranda</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </nav>

        @if ($isPreview)
            <aside class="mb-6 border border-gold bg-ivory p-4 text-sm text-brand-dark" role="status">
                Pratinjau privat. Halaman ini belum tentu telah dipublikasikan.
            </aside>
        @endif

        <article class="mx-auto max-w-3xl">
            <p class="text-sm text-brand">Informasi Alfikar</p>
            <h1 class="mt-2 font-display text-3xl text-brand-dark md:text-4xl">{{ $page['title'] }}</h1>
            @if (trim(strip_tags($safeContent)) === '')
                <div class="mt-6 border border-gold bg-ivory p-5 text-sm leading-7 text-brand-dark">
                    Konten halaman ini belum tersedia.
                </div>
            @else
                <div class="article-content mt-6 whitespace-pre-line border-t border-rule pt-6 text-sm leading-8 text-ink/80">{!! $safeContent !!}</div>
            @endif
        </article>
    </div>
@endsection
