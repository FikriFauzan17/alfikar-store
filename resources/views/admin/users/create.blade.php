@extends('layouts.admin')

@section('title', 'Tambah Pengguna')

@section('content')
    <p class="text-sm text-brand"><a href="{{ route('admin.users.index') }}">Pengguna</a> / Pengguna baru</p>
    <h1 class="mt-1 font-display text-3xl text-brand-dark">Tambah Pengguna</h1>
    <p class="mt-2 max-w-2xl text-sm text-ink/70">Buat akun dan tentukan hak aksesnya. Hanya administrator yang dapat membuka halaman ini.</p>

    <form class="mt-7 max-w-3xl" method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <section class="admin-card p-5 sm:p-6" aria-labelledby="user-details-heading">
            <h2 id="user-details-heading" class="font-display text-xl text-brand-dark">Informasi pengguna</h2>
            <p class="mt-1 text-sm text-ink/70">Nama dan alamat email untuk identitas akun.</p>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium" for="name">Nama lengkap</label>
                    <input
                        class="mt-1 w-full border border-rule bg-ivory px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        maxlength="255"
                        @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                        required
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-800" id="name-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium" for="email">Alamat email</label>
                    <input
                        class="mt-1 w-full border border-rule bg-ivory px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autocapitalize="none"
                        spellcheck="false"
                        maxlength="254"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        required
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-800" id="email-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="admin-card mt-5 p-5 sm:p-6" aria-labelledby="user-access-heading">
            <h2 id="user-access-heading" class="font-display text-xl text-brand-dark">Akses dan kata sandi</h2>
            <p class="mt-1 text-sm text-ink/70">Kata sandi minimal 12 karakter dan harus dimasukkan ulang untuk konfirmasi.</p>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium" for="password">Kata sandi</label>
                    <input
                        class="mt-1 w-full border border-rule bg-ivory px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        minlength="12"
                        @error('password') aria-invalid="true" aria-describedby="password-hint password-error" @else aria-describedby="password-hint" @enderror
                        required
                    >
                    <p class="mt-1 text-sm text-ink/70" id="password-hint">Gunakan minimal 12 karakter. Kata sandi tidak akan disimpan saat terjadi kesalahan validasi.</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-800" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium" for="password_confirmation">Konfirmasi kata sandi</label>
                    <input
                        class="mt-1 w-full border border-rule bg-ivory px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium" for="is_admin">Hak akses</label>
                    <select
                        class="mt-1 w-full border border-rule bg-ivory px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        id="is_admin"
                        name="is_admin"
                        @error('is_admin') aria-invalid="true" aria-describedby="is_admin-error" @enderror
                        required
                    >
                        <option value="0" @selected(old('is_admin', '0') === '0')>Pengguna</option>
                        <option value="1" @selected(old('is_admin') === '1')>Administrator</option>
                    </select>
                    @error('is_admin')
                        <p class="mt-1 text-sm text-red-800" id="is_admin-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <div class="mt-7 flex flex-wrap gap-3">
            <button class="bg-brand px-5 py-2 text-sm text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" type="submit">Simpan</button>
            <a class="border border-rule px-5 py-2 text-sm hover:border-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" href="{{ route('admin.users.index') }}">Batal</a>
        </div>
    </form>
@endsection
