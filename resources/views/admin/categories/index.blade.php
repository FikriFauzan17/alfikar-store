@extends('layouts.admin')

@section('title', 'Kategori')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Katalog</p>
            <h1 class="font-display text-3xl text-brand-dark">Kategori</h1>
        </div>
        <a class="admin-button" href="{{ route('admin.categories.create') }}">Tambah kategori</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table min-w-[620px]">
            <thead class="bg-ivory text-brand-dark">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Slug</th>
                    <th class="px-4 py-3 font-semibold">Produk</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr class="border-t border-rule">
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-ink/70">{{ $category->slug }}</td>
                        <td class="px-4 py-3">{{ $category->products_count }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :tone="$category->is_published ? 'success' : 'neutral'">{{ $category->is_published ? 'Terbit' : 'Draft' }}</x-admin.badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="admin-row-actions">
                                <a class="admin-row-action admin-row-action--edit" href="{{ route('admin.categories.edit', $category) }}">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none"><path d="m13.5 3.5 3 3M3 17l3.8-.8L16 7a2.1 2.1 0 0 0-3-3L3.8 13.2 3 17Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"/></svg>
                                    Ubah
                                </a>
                                <a class="admin-row-action admin-row-action--delete" href="{{ route('admin.categories.confirm-delete', $category) }}">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none"><path d="M4.5 6h11m-9.5 0 .6 10h7.8L15 6m-7-2h4l1 2M8 9v4m4-4v4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"/></svg>
                                    Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="admin-table__empty" colspan="5">Belum ada kategori. Gunakan “Tambah kategori” untuk membuat kategori pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">{{ $categories->links() }}</div>
@endsection