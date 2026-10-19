@extends('layouts.admin')

@section('title', $definition['title'])

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Konten publik · {{ $content['is_published'] ? 'Terbit' : 'Belum terbit' }}</p>
            <h1 class="font-display text-3xl text-brand-dark">{{ $definition['title'] }}</h1>
            <p class="admin-page-heading__description">{{ $definition['draft_notice'] }}</p>
        </div>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.information-pages.index') }}">Kembali</a>
    </div>

    <section class="admin-section max-w-4xl" aria-labelledby="information-editor-title">
        <div class="admin-section__heading">
            <div>
                <p class="admin-eyebrow">Editor teks</p>
                <h2 id="information-editor-title">Isi halaman</h2>
            </div>
            @if ($content['has_draft'])
                <span class="admin-badge">Draft tersimpan</span>
            @endif
        </div>
        <p class="mb-5 text-sm leading-6 text-ink/70">Gunakan teks biasa. Isi akan ditampilkan sebagai teks, bukan HTML. Pastikan konten sudah sesuai dan disetujui sebelum memilih Publikasikan.</p>

        <form method="POST" action="{{ route('admin.information-pages.update', ['page' => $slug]) }}">
            @csrf
            @method('PUT')
            <div class="space-y-5">
                <div>
                    <label class="admin-field-label" for="information-title">Judul halaman</label>
                    <input class="admin-field-input" id="information-title" name="title" type="text" maxlength="255" required value="{{ old('title', $content['title']) }}" aria-describedby="information-title-error" @error('title') aria-invalid="true" @enderror>
                    @error('title')<p class="admin-field-error" id="information-title-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-field-label" for="information-content">Konten</label>
                    <textarea class="admin-field-input min-h-72" id="information-content" name="content" rows="14" maxlength="30000" aria-describedby="information-content-help information-content-error" @error('content') aria-invalid="true" @enderror>{{ old('content', $content['content']) }}</textarea>
                    <p class="mt-1 text-xs text-ink/60" id="information-content-help">Maksimal 30.000 karakter. Konten kosong dapat disimpan sebagai draft, tetapi tidak dapat dipublikasikan.</p>
                    @error('content')<p class="admin-field-error" id="information-content-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button class="admin-button admin-button--quiet" type="submit" name="action" value="draft">Simpan Draft</button>
                <a class="admin-button admin-button--quiet" href="{{ route('admin.information-pages.preview', ['page' => $slug]) }}" target="_blank" rel="noopener">Pratinjau</a>
                <button class="admin-button admin-button--primary" type="submit" name="action" value="publish">Publikasikan</button>
            </div>
        </form>
    </section>
@endsection
