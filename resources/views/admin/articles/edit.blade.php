@extends('layouts.admin')

@section('title', 'Ubah artikel')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.articles.index') }}">Artikel</a> / Ubah</p>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <h1 class="mt-1 font-display text-3xl text-brand-dark">{{ $article->title }}</h1>
        <a class="text-sm text-brand underline underline-offset-4" href="{{ route('admin.articles.preview', $article) }}">Pratinjau</a>
    </div>
    <form class="admin-form-panel mt-7 max-w-4xl" method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.articles._form', ['article' => $article])
    </form>
@endsection
