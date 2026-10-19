@extends('layouts.admin')

@section('title', 'Produk')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Katalog</p>
            <h1 class="font-display text-3xl text-brand-dark">Produk</h1>
        </div>
        <a class="admin-button" href="{{ route('admin.products.create') }}">Tambah produk</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table min-w-[900px]">
            <thead class="bg-ivory text-brand-dark">
                <tr>
                    <th class="px-4 py-3 font-semibold">Buku</th>
                    <th class="px-4 py-3 font-semibold">Kategori</th>
                    <th class="px-4 py-3 font-semibold">Harga</th>
                    <th class="px-4 py-3 font-semibold">Stok</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="border-t border-rule">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($product->images->isNotEmpty())
                                    @php($primaryImage = $product->images->first())
                                    <img class="h-14 w-10 object-cover" src="{{ Storage::disk($primaryImage->disk)->url($primaryImage->variants[320] ?? $primaryImage->path) }}" alt="{{ $primaryImage->alt_text }}" width="40" height="56" loading="lazy">
                                @endif
                                <div>
                                    <p class="font-medium">{{ $product->title }}</p>
                                    <p class="text-xs text-ink/60">{{ $product->code }} · {{ $product->author }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $product->category->name }}</td>
                        <td class="px-4 py-3">Rp{{ number_format($product->unit_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ number_format($product->stock, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :tone="$product->is_published ? 'success' : 'neutral'">{{ $product->is_published ? 'Terbit' : 'Draft' }}</x-admin.badge>
                            @if ($product->stock === 0)<x-admin.badge class="mt-1" tone="danger">Stok habis</x-admin.badge>@endif
                            @if ($product->is_featured)<x-admin.badge class="mt-1" tone="warning">Unggulan</x-admin.badge>@endif
                            @if ($product->is_bestseller)<x-admin.badge class="mt-1" tone="neutral">Bestseller</x-admin.badge>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-brand underline underline-offset-4" href="{{ route('admin.products.edit', $product) }}">Ubah</a>
                            <a class="ml-4 text-red-800 underline underline-offset-4" href="{{ route('admin.products.confirm-delete', $product) }}">Hapus</a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="admin-table__empty" colspan="6">Belum ada produk. Tambahkan judul buku untuk mulai mengisi katalog.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">{{ $products->links() }}</div>
@endsection