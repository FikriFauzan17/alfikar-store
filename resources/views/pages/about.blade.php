@extends('layouts.storefront')

@section('title', 'Tentang '.($profile['profile_name'] ?? 'Toko Buku Alfikar'))
@section('meta_description', 'Profil '.($profile['profile_name'] ?? 'Toko Buku Alfikar').'.')
@section('canonical', route('pages.about'))
@section('og_title', 'Tentang '.($profile['profile_name'] ?? 'Toko Buku Alfikar'))
@section('og_description', 'Profil '.($profile['profile_name'] ?? 'Toko Buku Alfikar').'.')

@section('content')
    <div class="page-container max-w-4xl py-8 md:py-12">
        <nav class="mb-5 text-sm text-ink/60" aria-label="Breadcrumb"><ol class="flex gap-2"><li><a href="{{ route('home') }}">Beranda</a></li><li aria-hidden="true">/</li><li aria-current="page">Tentang</li></ol></nav>
        <p class="text-sm text-brand">{{ $profile['profile_name'] ?? 'Toko Buku Alfikar' }}</p>
        <h1 class="mt-2 font-display text-3xl text-brand-dark">{{ $profile['profile_name'] ?? 'Tentang Alfikar' }}</h1>
        @if (filled($profile['profile_description'] ?? null))
            <div class="mt-6 whitespace-pre-line text-base leading-8 text-ink/80">{{ $profile['profile_description'] }}</div>
        @else
            <p class="mt-6 border border-rule bg-white p-5 text-sm text-ink/70">Informasi profil belum tersedia.</p>
        @endif
        @if (filled($profile['profile_address'] ?? null))
            <section class="mt-8" aria-labelledby="address-title">
                <h2 class="font-display text-xl text-brand-dark" id="address-title">Alamat</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-ink/75">{{ $profile['profile_address'] }}</p>
            </section>
        @endif
    </div>
@endsection