@extends('layouts.admin')

@section('title', 'Ubah produk')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.products.index') }}">Produk</a> / Ubah</p>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <h1 class="mt-1 font-display text-3xl text-brand-dark">{{ $product->title }}</h1>
        <div class="flex flex-wrap gap-4">
            <a class="text-sm text-brand underline underline-offset-4" href="{{ route('admin.products.preview', $product) }}">Pratinjau</a>
            <a class="text-sm text-brand underline underline-offset-4" href="{{ route('admin.products.stock-adjustments.index', $product) }}">Riwayat stok</a>
            <a class="text-sm text-red-800 underline underline-offset-4" href="{{ route('admin.products.confirm-delete', $product) }}">Hapus produk</a>
        </div>
    </div>

    <section class="admin-card mt-5 flex flex-wrap items-center justify-between gap-4 p-5" aria-labelledby="product-stock-title">
        <div>
            <h2 class="font-display text-xl text-brand-dark" id="product-stock-title">Stok saat ini</h2>
            <p class="mt-1 text-2xl font-semibold text-brand-dark">{{ number_format($product->stock, 0, ',', '.') }} buku</p>
            <p class="mt-1 text-sm text-ink/70">Perubahan stok dicatat melalui penyesuaian dan tidak dapat diedit atau dihapus.</p>
        </div>
        <a class="admin-button" href="{{ route('admin.products.stock-adjustments.create', $product) }}">Sesuaikan stok</a>
    </section>

    <form class="admin-form-panel mt-7 max-w-5xl" method="POST" action="{{ route('admin.products.update', $product) }}">
        @csrf
        @method('PUT')
        @include('admin.products._form', ['product' => $product])
    </form>

    <section class="mt-12 border-t border-rule pt-8" aria-labelledby="gallery-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-brand">Foto</p>
                <h2 class="font-display text-2xl text-brand-dark" id="gallery-heading">Galeri produk</h2>
            </div>
            <p class="text-sm text-ink/70">{{ $product->images->count() }} / {{ config('image-processing.max_images') }} foto</p>
        </div>

        @if ($product->images->isNotEmpty())
            <form class="mt-5 border border-rule bg-white p-4" method="POST" action="{{ route('admin.products.images.order', $product) }}" data-product-image-order-form>
                @csrf
                @method('PATCH')
                <ol class="product-image-list grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-product-image-list>
                    @foreach ($product->images as $image)
                        <li class="product-image-card flex gap-3 border border-rule p-3" data-product-image-card>
                            <img class="h-36 w-24 shrink-0 object-cover" src="{{ Storage::disk($image->disk)->url($image->variants[320] ?? $image->path) }}" alt="{{ $image->alt_text }}" width="96" height="144" loading="lazy">
                            <div class="min-w-0">
                                <p class="product-image-primary text-xs font-semibold text-brand" data-product-image-primary @if ($image->sort_order !== 0) hidden @endif>Foto utama</p>
                                <p class="mt-1 break-all text-xs text-ink/70">{{ $image->width }} × {{ $image->height }} px</p>
                                <p class="break-all text-xs text-ink/70">Ukuran hasil tersimpan (semua varian): {{ number_format($image->file_size / 1024, 1) }} KiB</p>
                                <label class="mt-3 block text-xs font-medium" for="image-order-{{ $image->id }}">Urutan foto</label>
                                <input class="mt-1 w-20 border border-rule px-2 py-1" id="image-order-{{ $image->id }}" name="image_order[{{ $image->id }}]" type="number" min="0" max="{{ $product->images->count() - 1 }}" value="{{ old('image_order.'.$image->id, $image->sort_order) }}" required data-product-image-order>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button class="product-image-control" type="button" data-product-image-move="up" aria-label="Pindahkan foto {{ $loop->iteration }} ke urutan sebelumnya">Naik</button>
                                    <button class="product-image-control" type="button" data-product-image-move="down" aria-label="Pindahkan foto {{ $loop->iteration }} ke urutan berikutnya">Turun</button>
                                    <button class="product-image-control" type="button" data-product-image-primary-action>Jadikan foto utama</button>
                                </div>
                                <a class="mt-3 inline-block text-xs text-red-800 underline underline-offset-4" href="{{ route('admin.products.images.confirm-delete', [$product, $image]) }}">Hapus foto</a>
                            </div>
                        </li>
                    @endforeach
                </ol>
                @error('image_order')<p class="mt-3 text-sm text-red-800">{{ $message }}</p>@enderror
                <button class="mt-4 border border-brand px-4 py-2 text-sm text-brand-dark hover:bg-ivory" type="submit">Simpan urutan dan foto utama</button>
            </form>
        @else
            <p class="mt-4 text-sm text-ink/70">Belum ada foto untuk produk ini.</p>
        @endif

        @if ($product->images->count() < config('image-processing.max_images'))
            <form class="product-image-upload mt-6 max-w-2xl border border-rule bg-white p-4" method="POST" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data" data-product-image-upload data-existing-count="{{ $product->images->count() }}" data-image-limit="{{ config('image-processing.max_images') }}" data-max-bytes="{{ config('image-processing.max_upload_bytes') }}">
                @csrf
                <label class="block text-sm font-medium" for="images">Tambah foto</label>
                <input class="mt-2 block w-full text-sm" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required data-product-image-input>
                <p class="mt-2 text-xs text-ink/60">JPEG, PNG, atau WebP. Maksimal {{ config('image-processing.max_images') - $product->images->count() }} foto tambahan; ukuran tiap file maksimal {{ number_format(config('image-processing.max_upload_bytes') / 1024 / 1024, 1) }} MiB. Pilih hanya file yang berubah; foto lama tidak diproses ulang.</p>
                <div class="product-image-preview-list mt-4 grid gap-3" data-product-image-previews aria-live="polite"></div>
                @error('images')<p class="mt-3 text-sm text-red-800">{{ $message }}</p>@enderror
                <button class="mt-4 bg-brand px-4 py-2 text-sm text-white hover:bg-brand-dark" type="submit" data-product-image-submit>Unggah foto</button>
            </form>
        @endif
    </section>
@endsection