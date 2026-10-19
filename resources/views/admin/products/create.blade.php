@extends('layouts.admin')

@section('title', 'Tambah produk')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.products.index') }}">Produk</a> / Baru</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Tambah produk</h1>
    <form class="admin-form-panel mt-7 max-w-5xl" method="POST" action="{{ route('admin.products.store') }}">
        @csrf
        @include('admin.products._form')
    </form>
@endsection