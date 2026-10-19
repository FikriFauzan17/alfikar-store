<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#FAF9F6">
        <title>Masuk Admin | Toko Buku Alfikar</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-login">
        <main class="admin-login__layout">
            <aside class="admin-login__identity" aria-label="Identitas Alfikar">
                <a class="admin-login__brand" href="{{ route('home') }}" aria-label="Toko Buku Alfikar, kembali ke beranda">
                    <span class="admin-login__brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 48 48" fill="none">
                            <path d="M24 3.5 28.4 17l13.1-5.2-5.2 13.1 13.2 4.4-13.2 4.4 5.2 13.1-13.1-5.2L24 55l-4.4-13.4-13.1 5.2 5.2-13.1L-1.5 29.3l13.2-4.4-5.2-13.1L19.6 17 24 3.5Z" transform="translate(4 -5) scale(.83)" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M24 14 34 24 24 34 14 24 24 14Z" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="24" cy="24" r="3" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="admin-login__brand-copy">
                        <span class="admin-login__brand-name">Alfikar</span>
                        <span class="admin-login__brand-descriptor">Toko Buku</span>
                    </span>
                </a>

                <div class="admin-login__message">
                    <span class="admin-login__eyebrow">Ruang Admin</span>
                    <h1>Kelola Koleksi,<br>Hadirkan Inspirasi</h1>
                    <p>Ruang untuk merawat setiap judul dan cerita yang dibagikan Alfikar.</p>
                </div>

                <span class="admin-login__identity-note">Buku, ilmu, dan inspirasi.</span>
            </aside>

            <section class="admin-login__content" aria-labelledby="login-title">
                <div class="admin-login__form-wrap">
                    <a class="admin-login__mobile-brand" href="{{ route('home') }}" aria-label="Toko Buku Alfikar, kembali ke beranda">
                        <span class="admin-login__brand-mark" aria-hidden="true">
                            <svg viewBox="0 0 48 48" fill="none">
                                <path d="M24 3.5 28.4 17l13.1-5.2-5.2 13.1 13.2 4.4-13.2 4.4 5.2 13.1-13.1-5.2L24 55l-4.4-13.4-13.1 5.2 5.2-13.1L-1.5 29.3l13.2-4.4-5.2-13.1L19.6 17 24 3.5Z" transform="translate(4 -5) scale(.83)" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M24 14 34 24 24 34 14 24 24 14Z" stroke="currentColor" stroke-width="1.5"/>
                                <circle cx="24" cy="24" r="3" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="admin-login__brand-copy">
                            <span class="admin-login__brand-name">Alfikar</span>
                            <span class="admin-login__brand-descriptor">Toko Buku</span>
                        </span>
                    </a>

                    <div class="admin-login__heading">
                        <span class="admin-login__eyebrow">Selamat datang kembali</span>
                        <h2 id="login-title">Masuk ke Admin Alfikar</h2>
                        <p>Masuk untuk mengelola koleksi dan kabar terbaru toko.</p>
                    </div>

                    @if ($errors->has('credentials'))
                        <div class="admin-login__alert" role="alert">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M10 6v4m0 3h.01" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"/>
                            </svg>
                            <span>{{ $errors->first('credentials') }}</span>
                        </div>
                    @elseif ($errors->has('account'))
                        <div class="admin-login__alert" role="alert">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M10 6v4m0 3h.01" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"/>
                            </svg>
                            <span>{{ $errors->first('account') }}</span>
                        </div>
                    @elseif ($errors->any())
                        <div class="admin-login__alert" role="alert">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M10 6v4m0 3h.01" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"/>
                            </svg>
                            <span>Periksa kembali email dan kata sandi.</span>
                        </div>
                    @endif

                    <form class="admin-login__form" method="POST" action="{{ route('admin.login') }}" data-admin-login-form>
                        @csrf

                        <div class="admin-login__field">
                            <label for="email">Alamat email</label>
                            <div class="admin-login__input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="m5 7 7 5.5L19 7" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                                </svg>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="username"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    placeholder="nama@email.com"
                                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                    required
                                >
                            </div>
                            @error('email')
                                <p class="admin-login__field-error" id="email-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="admin-login__field">
                            <label for="password">Kata sandi</label>
                            <div class="admin-login__input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="4.5" y="10" width="15" height="10" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v2" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"/>
                                </svg>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    placeholder="Masukkan kata sandi"
                                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                    required
                                >
                                <button
                                    class="admin-login__password-toggle"
                                    type="button"
                                    aria-label="Tampilkan kata sandi"
                                    aria-controls="password"
                                    aria-pressed="false"
                                    data-password-toggle
                                >
                                    <svg class="admin-login__eye-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6S2.5 12 2.5 12Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                                        <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.5"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="admin-login__field-error" id="password-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button class="admin-login__submit" type="submit" data-login-submit>
                            <span data-login-submit-label>Masuk</span>
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"/>
                            </svg>
                        </button>
                    </form>

                    <a class="admin-login__back-link" href="{{ route('home') }}">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M16 10H4m5-5-5 5 5 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                        </svg>
                        Kembali ke Website
                    </a>
                </div>
            </section>
        </main>
    </body>
</html>
