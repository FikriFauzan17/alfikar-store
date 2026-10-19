@extends('layouts.admin')

@section('title', 'Ubah kategori')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.categories.index') }}">Kategori</a> / Ubah</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">{{ $category->name }}</h1>
    <form class="admin-form-panel mt-7 max-w-3xl" method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.categories._form', ['category' => $category])
    </form>
@endsection