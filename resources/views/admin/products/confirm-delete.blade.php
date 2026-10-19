@extends('layouts.admin')

@section('title', 'Konfirmasi hapus produk')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.products.index') }}">Produk</a> / Hapus</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Hapus produk?</h1>
    <div class="admin-dialog-card mt-6">
        <p><strong>{{ $product->title }}</strong> ({{ $product->code }}) akan dihapus.</p>
        <p class="mt-2 text-sm text-ink/70">{{ $product->images_count }} foto terkait juga akan dihapus.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                @csrf
                @method('DELETE')
                <button class="bg-red-800 px-5 py-2 text-sm text-white hover:bg-red-900" type="submit">Konfirmasi hapus</button>
            </form>
            <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.products.edit', $product) }}">Batal</a>
        </div>
    </div>
@endsection