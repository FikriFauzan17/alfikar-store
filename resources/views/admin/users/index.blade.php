@extends('layouts.admin')

@section('title', 'Pengguna')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Akses akun</p>
            <h1 class="font-display text-3xl text-brand-dark">Pengguna</h1>
            <p class="admin-page-heading__description">Kelola akun yang dapat mengakses administrasi Alfikar.</p>
        </div>
        <a class="admin-button" href="{{ route('admin.users.create') }}">Tambah Pengguna</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <caption class="sr-only">Daftar pengguna dan status aksesnya</caption>
            <thead>
                <tr>
                    <th scope="col">Pengguna</th>
                    <th scope="col">Hak akses</th>
                    <th scope="col">Status akun</th>
                    <th scope="col">2FA</th>
                    <th scope="col">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <strong class="block">{{ $user->name }}</strong>
                            <span class="text-sm text-ink/70">{{ $user->email }}</span>
                        </td>
                        <td>
                            <span class="admin-badge {{ $user->is_admin ? 'admin-badge--success' : 'admin-badge--neutral' }}">
                                {{ $user->is_admin ? 'Administrator' : 'Pengguna' }}
                            </span>
                        </td>
                        <td>
                            <span class="admin-badge {{ $user->is_active ? 'admin-badge--success' : 'admin-badge--danger' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <span class="admin-badge {{ $user->hasTwoFactorEnabled() ? 'admin-badge--success' : 'admin-badge--warning' }}">
                                {{ $user->hasTwoFactorEnabled() ? 'Aktif' : 'Belum aktif' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                <button class="admin-button {{ $user->is_active ? 'admin-button--quiet' : '' }}" type="submit">
                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="admin-table__empty" colspan="5">Belum ada akun pengguna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="admin-pagination">{{ $users->links() }}</div>
    @endif
@endsection
