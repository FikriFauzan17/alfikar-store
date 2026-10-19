@extends('layouts.storefront')

@section('canonical', $isPreview ? request()->url() : route('home'))
@section('robots', $isPreview ? 'noindex,nofollow' : 'index,follow')

@push('structured-data')
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">@json($organizationStructuredData)</script>
@endpush

@section('content')
    <div class="page-container">
        @if ($isPreview)
            <aside class="mt-6 border border-gold bg-ivory p-4 text-sm text-brand-dark" role="status">Pratinjau admin — perubahan beranda belum diterbitkan.</aside>
        @endif
        @foreach (array_keys($homeSections) as $section)
            @include('home.sections.'.$section)
        @endforeach
    </div>
@endsection
