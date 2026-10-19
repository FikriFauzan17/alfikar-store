@extends('layouts.admin')

@section('title', 'Halaman informasi')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Konten publik</p>
            <h1 class="font-display text-3xl text-brand-dark">Halaman informasi</h1>
            <p class="admin-page-heading__description">Kelola isi kebijakan yang telah disediakan dan disetujui. Halaman baru tidak diterbitkan otomatis.</p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($pages as $slug => $page)
            <section class="admin-section">
                <div class="admin-section__heading">
                    <div>
                        <p class="admin-eyebrow">{{ $page['is_published'] ? 'Sudah terbit' : 'Belum terbit' }}</p>
                        <h2>{{ $page['definition']['title'] }}</h2>
                    </div>
                    @if ($page['has_draft'])
                        <span class="admin-badge">Draft tersimpan</span>
                    @endif
                </div>
                <p class="text-sm leading-6 text-ink/70">{{ $page['definition']['draft_notice'] }}</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a class="admin-button admin-button--primary" href="{{ route('admin.information-pages.edit', ['page' => $slug]) }}">Kelola halaman</a>
                    <a class="admin-button admin-button--quiet" href="{{ route('admin.information-pages.preview', ['page' => $slug]) }}" target="_blank" rel="noopener">Pratinjau</a>
                </div>
            </section>
        @endforeach
    </div>
@endsection
