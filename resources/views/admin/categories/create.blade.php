@extends('layouts.admin')

@section('title', 'Tambah kategori')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.categories.index') }}">Kategori</a> / Baru</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Tambah kategori</h1>
    <form class="admin-form-panel mt-7 max-w-3xl" method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.categories._form')
    </form>
@endsection