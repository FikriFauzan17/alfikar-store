@extends('layouts.admin')

@section('title', 'Tambah artikel')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.articles.index') }}">Artikel</a> / Baru</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Tambah artikel</h1>
    <form class="admin-form-panel mt-7 max-w-4xl" method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.articles._form')
    </form>
@endsection
