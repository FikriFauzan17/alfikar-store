@extends('layouts.admin')

@section('title', 'Riwayat stok')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow"><a href="{{ route('admin.products.edit', $product) }}">Produk</a> / Riwayat</p>
            <h1 class="font-display text-3xl text-brand-dark">Riwayat stok</h1>
            <p class="mt-1 text-sm text-ink/70">{{ $product->title }} · stok saat ini {{ number_format($product->stock, 0, ',', '.') }}</p>
        </div>
        <a class="admin-button" href="{{ route('admin.products.stock-adjustments.create', $product) }}">Sesuaikan stok</a>
    </div>

    <form class="admin-card my-5 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end" method="GET">
        <div>
            <label class="block text-sm font-medium" for="reason">Alasan</label>
            <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="reason" name="reason">
                <option value="">Semua alasan</option>
                <option value="whatsapp_sale" @selected(($filters['reason'] ?? '') === 'whatsapp_sale')>Penjualan WhatsApp</option>
                <option value="goods_received" @selected(($filters['reason'] ?? '') === 'goods_received')>Barang masuk</option>
                <option value="correction" @selected(($filters['reason'] ?? '') === 'correction')>Koreksi</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium" for="direction">Arah</label>
            <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="direction" name="direction">
                <option value="">Semua arah</option>
                <option value="decrease" @selected(($filters['direction'] ?? '') === 'decrease')>Pengurangan</option>
                <option value="increase" @selected(($filters['direction'] ?? '') === 'increase')>Penambahan</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium" for="from">Dari tanggal</label>
            <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to">Sampai tanggal</label>
            <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div class="flex gap-3">
            <button class="admin-button" type="submit">Filter</button>
            <a class="border border-rule px-4 py-2 text-sm" href="{{ route('admin.products.stock-adjustments.index', $product) }}">Reset</a>
        </div>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table min-w-[900px]">
            <thead class="bg-ivory text-brand-dark">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Waktu</th>
                    <th class="px-4 py-3 text-left font-semibold">Alasan</th>
                    <th class="px-4 py-3 text-left font-semibold">Perubahan</th>
                    <th class="px-4 py-3 text-left font-semibold">Stok sebelum → sesudah</th>
                    <th class="px-4 py-3 text-left font-semibold">Admin</th>
                    <th class="px-4 py-3 text-left font-semibold">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($adjustments as $adjustment)
                    <tr class="border-t border-rule">
                        <td class="px-4 py-3">{{ $adjustment->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3">
                            {{ match ($adjustment->reason) {
                                'whatsapp_sale' => 'Penjualan WhatsApp',
                                'goods_received' => 'Barang masuk',
                                default => 'Koreksi',
                            } }}
                        </td>
                        <td class="px-4 py-3">{{ $adjustment->direction === 'increase' ? '+' : '−' }}{{ number_format($adjustment->quantity, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ number_format($adjustment->stock_before, 0, ',', '.') }} → {{ number_format($adjustment->stock_after, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $adjustment->actor_name }}</td>
                        <td class="px-4 py-3">{{ $adjustment->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td class="admin-table__empty" colspan="6">Belum ada penyesuaian stok yang sesuai filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $adjustments->links() }}</div>
@endsection
