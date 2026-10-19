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
                        <td class="px-4 py-3 text-right">
                            <a class="text-brand underline underline-offset-4" href="{{ route('admin.categories.edit', $category) }}">Ubah</a>
                            <a class="ml-4 text-red-800 underline underline-offset-4" href="{{ route('admin.categories.confirm-delete', $category) }}">Hapus</a>
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