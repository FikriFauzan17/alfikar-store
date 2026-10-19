@extends('layouts.admin')

@section('title', 'Ubah banner')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.banners.index') }}">Banner</a> / Ubah</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">{{ $banner->title }}</h1>
    <form class="admin-form-panel mt-7 max-w-4xl" method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.banners._form', ['banner' => $banner])
    </form>
@endsection
