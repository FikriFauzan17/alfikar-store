@extends('layouts.admin')

@section('title', 'Banner')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Konten beranda</p>
            <h1 class="font-display text-3xl text-brand-dark">Banner</h1>
        </div>
        <a class="admin-button" href="{{ route('admin.banners.create') }}">Tambah banner</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table min-w-[740px]">
            <thead class="bg-ivory text-brand-dark">
                <tr>
                    <th class="px-4 py-3 font-semibold">Judul</th>
                    <th class="px-4 py-3 font-semibold">Urutan</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Periode</th>
                    <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($banners as $banner)
                    <tr class="border-t border-rule">
                        <td class="px-4 py-3 font-medium">{{ $banner->title }}</td>
                        <td class="px-4 py-3">{{ $banner->sort_order }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :tone="$banner->is_active ? 'success' : 'neutral'">{{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}</x-admin.badge>
                        </td>
                        <td class="px-4 py-3">{{ $banner->starts_at?->format('d/m/Y H:i') ?? 'Tanpa batas mulai' }} – {{ $banner->ends_at?->format('d/m/Y H:i') ?? 'Tanpa batas akhir' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-brand underline underline-offset-4" href="{{ route('admin.banners.edit', $banner) }}">Ubah</a>
                            <a class="ml-4 text-red-800 underline underline-offset-4" href="{{ route('admin.banners.confirm-delete', $banner) }}">Hapus</a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="admin-table__empty" colspan="5">Belum ada banner untuk beranda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">{{ $banners->links() }}</div>
@endsection
