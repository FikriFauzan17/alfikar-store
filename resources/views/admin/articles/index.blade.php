@extends('layouts.admin')

@section('title', 'Artikel')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Konten</p>
            <h1 class="font-display text-3xl text-brand-dark">Artikel</h1>
        </div>
        <a class="admin-button" href="{{ route('admin.articles.create') }}">Tambah artikel</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table min-w-[760px]">
            <thead class="bg-ivory text-brand-dark">
                <tr>
                    <th class="px-4 py-3 font-semibold">Judul</th>
                    <th class="px-4 py-3 font-semibold">Slug</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Jadwal/terbit</th>
                    <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    <tr class="border-t border-rule">
                        <td class="px-4 py-3 font-medium">{{ $article->title }}</td>
                        <td class="px-4 py-3 text-ink/70">{{ $article->slug }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :tone="$article->status->value === 'published' ? 'success' : ($article->status->value === 'scheduled' ? 'warning' : 'neutral')">{{ ucfirst($article->status->value) }}</x-admin.badge>
                        </td>
                        <td class="px-4 py-3">{{ $article->published_at?->translatedFormat('d F Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-brand underline underline-offset-4" href="{{ route('admin.articles.edit', $article) }}">Ubah</a>
                            <a class="ml-4 text-red-800 underline underline-offset-4" href="{{ route('admin.articles.confirm-delete', $article) }}">Hapus</a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="admin-table__empty" colspan="5">Belum ada artikel. Buat artikel untuk berbagi informasi terbaru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">{{ $articles->links() }}</div>
@endsection
