@extends('layouts.admin')

@section('title', 'Keamanan Akun')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Perlindungan akun</p>
            <h1 class="font-display text-3xl text-brand-dark">Keamanan Akun</h1>
            <p class="admin-page-heading__description">Kelola autentikasi dua langkah dan sesi yang sedang aktif.</p>
        </div>
    </div>

    <section class="admin-card p-5 sm:p-6" aria-labelledby="two-factor-heading">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 id="two-factor-heading" class="font-display text-xl text-brand-dark">Verifikasi dua langkah (TOTP)</h2>
                <p class="mt-1 max-w-2xl text-sm text-ink/70">Gunakan aplikasi autentikator. Penyiapan memerlukan kata sandi saat ini dan kode verifikasi.</p>
            </div>
            <span class="admin-badge {{ $user->hasTwoFactorEnabled() ? 'admin-badge--success' : 'admin-badge--warning' }}">
                {{ $user->hasTwoFactorEnabled() ? 'Aktif' : 'Belum aktif' }}
            </span>
        </div>

        @if ($user->hasTwoFactorEnabled())
            <p class="mt-4 text-sm text-ink/70">
                {{ count($user->totp_recovery_codes ?? []) }} recovery code tersedia. Kode yang telah digunakan tidak dapat dipakai kembali.
            </p>
            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                <form class="admin-form-panel" method="POST" action="{{ route('admin.security.two-factor.recovery-codes') }}">
                    @csrf
                    <h3 class="font-semibold text-brand-dark">Buat ulang recovery code</h3>
                    <p class="mt-1 text-sm text-ink/70">Semua recovery code lama akan dicabut. Verifikasi kata sandi dan faktor yang masih berlaku.</p>
                    <label class="mt-4 block" for="recovery-current-password">Kata sandi saat ini</label>
                    <input class="mt-1 w-full" id="recovery-current-password" name="current_password" type="password" autocomplete="current-password" required>
                    <label class="mt-4 block" for="recovery-code">Kode autentikasi atau recovery code</label>
                    <input class="mt-1 w-full" id="recovery-code" name="code" type="text" autocomplete="one-time-code" required>
                    @error('current_password') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                    @error('code') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                    <button class="admin-button mt-5" type="submit">Buat Recovery Code Baru</button>
                </form>

                <form class="admin-form-panel" method="POST" action="{{ route('admin.security.two-factor.disable') }}">
                    @csrf
                    <h3 class="font-semibold text-brand-dark">Nonaktifkan verifikasi dua langkah</h3>
                    <p class="mt-1 text-sm text-ink/70">Tindakan ini memerlukan kata sandi dan TOTP atau recovery code yang belum digunakan.</p>
                    <label class="mt-4 block" for="disable-current-password">Kata sandi saat ini</label>
                    <input class="mt-1 w-full" id="disable-current-password" name="current_password" type="password" autocomplete="current-password" required>
                    <label class="mt-4 block" for="disable-code">Kode autentikasi atau recovery code</label>
                    <input class="mt-1 w-full" id="disable-code" name="code" type="text" autocomplete="one-time-code" required>
                    @error('current_password') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                    @error('code') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                    <button class="admin-button mt-5 bg-red-800" type="submit">Nonaktifkan 2FA</button>
                </form>
            </div>
        @elseif (is_string($setupSecret))
            <div class="mt-5 rounded border border-rule bg-ivory p-4">
                <h3 class="font-semibold text-brand-dark">Tambahkan kunci di aplikasi autentikator</h3>
                <p class="mt-1 text-sm text-ink/70">Pilih penyiapan manual, masukkan akun email admin dan kunci berikut, lalu kirim kode enam digit terbaru.</p>
                <code class="mt-3 block break-all rounded bg-white p-3 text-sm" aria-label="Kunci rahasia TOTP">{{ $setupSecret }}</code>
            </div>
            <form class="admin-form-panel mt-5 max-w-xl" method="POST" action="{{ route('admin.security.two-factor.enable') }}">
                @csrf
                <label class="block" for="enable-current-password">Kata sandi saat ini</label>
                <input class="mt-1 w-full" id="enable-current-password" name="current_password" type="password" autocomplete="current-password" required>
                <label class="mt-4 block" for="enable-code">Kode enam digit</label>
                <input class="mt-1 w-full" id="enable-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                @error('current_password') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                @error('code') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                <div class="mt-5 flex flex-wrap gap-3">
                    <button class="admin-button" type="submit">Aktifkan 2FA</button>
                </div>
            </form>
            <form class="mt-3" method="POST" action="{{ route('admin.security.two-factor.cancel') }}">
                @csrf
                <button class="admin-button admin-button--quiet" type="submit">Batal</button>
            </form>
        @else
            <form class="admin-form-panel mt-5 max-w-xl" method="POST" action="{{ route('admin.security.two-factor.setup') }}">
                @csrf
                <label class="block" for="setup-current-password">Kata sandi saat ini</label>
                <input class="mt-1 w-full" id="setup-current-password" name="current_password" type="password" autocomplete="current-password" required>
                @error('current_password') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                <button class="admin-button mt-5" type="submit">Mulai Pengaturan 2FA</button>
            </form>
        @endif
    </section>

    <section class="admin-card mt-6 p-5 sm:p-6" aria-labelledby="sessions-heading">
        <h2 id="sessions-heading" class="font-display text-xl text-brand-dark">Sesi Aktif</h2>
        <p class="mt-1 text-sm text-ink/70">Daftar dan pencabutan sesi tersedia saat aplikasi menggunakan session driver database.</p>

        @if ($activeSessions)
            <div class="mt-5 grid gap-3">
                @forelse ($activeSessions as $session)
                    <article class="rounded border border-rule p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong>{{ $session->ip_address ?: 'Alamat IP tidak tersedia' }}</strong>
                                    @if ($session->is_current)
                                        <span class="admin-badge admin-badge--success">Sesi ini</span>
                                    @endif
                                </div>
                                <p class="mt-1 break-words text-sm text-ink/70">{{ $session->user_agent ?: 'Perangkat tidak diketahui' }}</p>
                                <p class="mt-1 text-xs text-ink/60">{{ \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</p>
                            </div>
                            @unless ($session->is_current)
                                <form method="POST" action="{{ route('admin.security.sessions.destroy', $session->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-button admin-button--quiet" type="submit">Cabut sesi</button>
                                </form>
                            @endunless
                        </div>
                    </article>
                @empty
                    <p class="admin-empty-state">Tidak ada sesi database aktif untuk akun ini.</p>
                @endforelse
            </div>
            @if ($activeSessions->hasPages())
                <div class="admin-pagination">{{ $activeSessions->links() }}</div>
            @endif
        @else
            <p class="admin-empty-state mt-5">Session driver saat ini tidak mendukung daftar/pencabutan sesi melalui tabel database.</p>
        @endif
    </section>
@endsection
