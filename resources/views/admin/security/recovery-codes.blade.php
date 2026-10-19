@extends('layouts.admin')

@section('title', 'Recovery Code')

@section('content')
    <section class="admin-card max-w-3xl p-5 sm:p-6" aria-labelledby="recovery-codes-heading">
        <p class="admin-eyebrow">Penyimpanan satu kali</p>
        <h1 id="recovery-codes-heading" class="mt-1 font-display text-3xl text-brand-dark">Simpan Recovery Code</h1>
        <p class="mt-3 text-sm text-ink/70">{{ $message }} Salin atau simpan sekarang; kode tidak disimpan dalam sesi dan tidak dapat ditampilkan kembali.</p>
        <ul class="mt-5 grid gap-3 sm:grid-cols-2" aria-label="Recovery code satu kali">
            @foreach ($recoveryCodes as $recoveryCode)
                <li class="rounded border border-rule bg-ivory p-3">
                    <code>{{ $recoveryCode }}</code>
                </li>
            @endforeach
        </ul>
        <a class="admin-button mt-6" href="{{ route('admin.security.index') }}">Kembali ke Keamanan Akun</a>
    </section>
@endsection
