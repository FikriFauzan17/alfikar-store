@extends('layouts.storefront')

@section('title', 'Kontak '.($contact['profile_name'] ?? 'Toko Buku Alfikar'))
@section('meta_description', 'Informasi kontak '.($contact['profile_name'] ?? 'Toko Buku Alfikar').'.')
@section('canonical', route('pages.contact'))
@section('og_title', 'Kontak '.($contact['profile_name'] ?? 'Toko Buku Alfikar'))
@section('og_description', 'Informasi kontak '.($contact['profile_name'] ?? 'Toko Buku Alfikar').'.')

@section('content')
    <div class="page-container max-w-4xl py-8 md:py-12">
        <nav class="mb-5 text-sm text-ink/60" aria-label="Breadcrumb"><ol class="flex gap-2"><li><a href="{{ route('home') }}">Beranda</a></li><li aria-hidden="true">/</li><li aria-current="page">Kontak</li></ol></nav>
        <p class="text-sm text-brand">{{ $contact['profile_name'] ?? 'Toko Buku Alfikar' }}</p>
        <h1 class="mt-2 font-display text-3xl text-brand-dark">Kontak</h1>

        @php($hasContact = filled($contact['contact_address'] ?? null) || filled($contact['contact_email'] ?? null) || filled($contact['contact_phone'] ?? null) || $whatsappAvailable || $socialLinks !== [] || $mapUrl !== null)
        @if ($hasContact)
            <dl class="mt-6 divide-y divide-rule border-y border-rule bg-white px-5">
                @if (filled($contact['contact_address'] ?? null))<div class="grid gap-2 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-sm font-medium text-brand-dark">Alamat</dt><dd class="whitespace-pre-line text-sm">{{ $contact['contact_address'] }}</dd></div>@endif
                @if ($mapUrl)<div class="grid gap-2 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-sm font-medium text-brand-dark">Peta</dt><dd class="text-sm"><a class="text-brand underline underline-offset-4" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">Lihat lokasi di Google Maps</a></dd></div>@endif
                @if (filled($contact['contact_email'] ?? null) && filter_var($contact['contact_email'], FILTER_VALIDATE_EMAIL))<div class="grid gap-2 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-sm font-medium text-brand-dark">Email</dt><dd class="text-sm"><a href="mailto:{{ $contact['contact_email'] }}">{{ $contact['contact_email'] }}</a></dd></div>@endif
                @if (filled($contact['contact_phone'] ?? null))<div class="grid gap-2 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-sm font-medium text-brand-dark">Telepon</dt><dd class="text-sm">{{ $contact['contact_phone'] }}</dd></div>@endif
                @if ($whatsappAvailable)<div class="grid gap-2 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-sm font-medium text-brand-dark">WhatsApp</dt><dd class="text-sm"><a class="text-brand underline underline-offset-4" href="https://wa.me/{{ preg_replace('/\D/', '', $contact['whatsapp_number']) }}" rel="noopener noreferrer">Hubungi Alfikar</a></dd></div>@endif
                @if ($socialLinks !== [])<div class="grid gap-2 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-sm font-medium text-brand-dark">Sosial</dt><dd class="flex flex-wrap gap-4 text-sm">@foreach ($socialLinks as $label => $url)<a class="text-brand underline underline-offset-4" href="{{ $url }}" rel="noopener noreferrer">{{ $label }}</a>@endforeach</dd></div>@endif
            </dl>
        @else
            <p class="mt-6 border border-rule bg-white p-5 text-sm text-ink/70">Informasi kontak belum tersedia.</p>
        @endif
    </div>
@endsection