@extends('layouts.admin')

@section('title', 'Konfirmasi hapus foto')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.products.edit', $product) }}">{{ $product->title }}</a> / Hapus foto</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Hapus foto produk?</h1>
    <div class="admin-dialog-card mt-6 flex max-w-xl flex-wrap gap-5">
        <img class="h-48 w-32 object-cover" src="{{ Storage::disk($image->disk)->url($image->variants[320] ?? $image->path) }}" alt="{{ $image->alt_text }}" width="128" height="192">
        <div class="self-center">
            <p>{{ $image->alt_text }}</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="bg-red-800 px-5 py-2 text-sm text-white hover:bg-red-900" type="submit">Konfirmasi hapus foto</button>
                </form>
                <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.products.edit', $product) }}">Batal</a>
            </div>
        </div>
    </div>
@endsection