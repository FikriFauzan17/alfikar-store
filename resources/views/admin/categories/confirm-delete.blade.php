@extends('layouts.admin')

@section('title', 'Konfirmasi hapus kategori')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.categories.index') }}">Kategori</a> / Hapus</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Hapus kategori?</h1>
    <div class="admin-dialog-card mt-6">
        <p>Kategori <strong>{{ $category->name }}</strong> akan dihapus.</p>
        <p class="mt-2 text-sm text-ink/70">Kategori yang masih dipakai produk tidak dapat dihapus.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                @csrf
                @method('DELETE')
                <button class="bg-red-800 px-5 py-2 text-sm text-white hover:bg-red-900" type="submit">Konfirmasi hapus</button>
            </form>
            <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.categories.index') }}">Batal</a>
        </div>
    </div>
@endsection