@extends('layouts.admin')

@section('title', 'Konfirmasi hapus artikel')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.articles.index') }}">Artikel</a> / Hapus</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Hapus artikel?</h1>
    <div class="admin-dialog-card mt-6">
        <p>Artikel <strong>{{ $article->title }}</strong> akan dihapus.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.articles.destroy', $article) }}">
                @csrf
                @method('DELETE')
                <button class="bg-red-800 px-5 py-2 text-sm text-white hover:bg-red-900" type="submit">Konfirmasi hapus</button>
            </form>
            <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.articles.index') }}">Batal</a>
        </div>
    </div>
@endsection
