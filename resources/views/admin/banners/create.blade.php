@extends('layouts.admin')

@section('title', 'Tambah banner')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.banners.index') }}">Banner</a> / Baru</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Tambah banner</h1>
    <form class="admin-form-panel mt-7 max-w-4xl" method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.banners._form')
    </form>
@endsection
