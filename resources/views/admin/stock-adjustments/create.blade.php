@extends('layouts.admin')

@section('title', 'Penyesuaian stok')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.products.edit', $product) }}">Produk</a> / Stok</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Penyesuaian stok</h1>
    <section class="admin-card mt-5 p-5" aria-labelledby="stock-adjustment-product">
        <h2 class="font-display text-xl text-brand-dark" id="stock-adjustment-product">{{ $product->title }}</h2>
        <p class="mt-1 text-sm text-ink/70">Kode {{ $product->code }} · stok saat ini {{ number_format($product->stock, 0, ',', '.') }}</p>
    </section>

    <form class="admin-form-panel mt-6 max-w-3xl" method="POST" action="{{ route('admin.products.stock-adjustments.store', $product) }}" data-stock-adjustment-form>
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium" for="reason">Alasan</label>
                <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="reason" name="reason" required>
                    <option value="">Pilih alasan</option>
                    <option value="whatsapp_sale" @selected(old('reason') === 'whatsapp_sale')>Penjualan WhatsApp terkonfirmasi</option>
                    <option value="goods_received" @selected(old('reason') === 'goods_received')>Barang masuk</option>
                    <option value="correction" @selected(old('reason') === 'correction')>Koreksi stok</option>
                </select>
                @error('reason')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium" for="direction">Penyesuaian</label>
                <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="direction" name="direction" required>
                    <option value="">Pilih arah perubahan</option>
                    <option value="decrease" @selected(old('direction') === 'decrease')>Kurangi stok</option>
                    <option value="increase" @selected(old('direction') === 'increase')>Tambah stok</option>
                </select>
                @error('direction')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium" for="quantity">Jumlah buku</label>
                <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="quantity" name="quantity" type="number" min="1" max="4294967295" step="1" value="{{ old('quantity') }}" required>
                @error('quantity')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium" for="notes">Catatan tambahan (opsional)</label>
                <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2" id="notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
            </div>
        </div>
        @error('submission_token')<p class="mt-4 text-sm text-red-800">{{ $message }}</p>@enderror
        <p class="mt-4 text-xs text-ink/60">Penjualan WhatsApp hanya dicatat setelah transaksi dikonfirmasi. Menghubungi WhatsApp tidak mengubah stok.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <button class="admin-button" type="submit" data-stock-adjustment-submit>Simpan penyesuaian</button>
            <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.products.edit', $product) }}">Batal</a>
        </div>
    </form>
@endsection
