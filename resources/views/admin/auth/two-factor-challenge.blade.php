<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#FAF9F6">
        <meta name="robots" content="noindex, nofollow">
        <title>Verifikasi Admin | Toko Buku Alfikar</title>
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
                    <span class="admin-login__eyebrow">Perlindungan Akun</span>
                    <h1>Verifikasi<br>Identitas Anda</h1>
                    <p>Masukkan kode dari aplikasi autentikator atau satu recovery code.</p>
                </div>
                <span class="admin-login__identity-note">Buku, ilmu, dan inspirasi.</span>
            </aside>

            <section class="admin-login__content" aria-labelledby="two-factor-title">
                <div class="admin-login__form-wrap">
                    <a class="admin-login__mobile-brand" href="{{ route('home') }}" aria-label="Toko Buku Alfikar, kembali ke beranda">
                        <span class="admin-login__brand-mark" aria-hidden="true">A</span>
                        <span class="admin-login__brand-copy">
                            <span class="admin-login__brand-name">Alfikar</span>
                            <span class="admin-login__brand-descriptor">Toko Buku</span>
                        </span>
                    </a>

                    <div class="admin-login__heading">
                        <span class="admin-login__eyebrow">Langkah kedua</span>
                        <h2 id="two-factor-title">Verifikasi dua langkah</h2>
                        <p>Gunakan kode enam digit dari aplikasi autentikator. Recovery code hanya dapat digunakan sekali.</p>
                    </div>

                    @if ($errors->any())
                        <div class="admin-login__alert" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form class="admin-login__form" method="POST" action="{{ route('admin.two-factor.verify') }}">
                        @csrf
                        <div class="admin-login__field">
                            <label for="code">Kode autentikasi atau recovery code</label>
                            <div class="admin-login__input-wrap">
                                <input
                                    id="code"
                                    name="code"
                                    type="text"
                                    inputmode="text"
                                    autocomplete="one-time-code"
                                    autocapitalize="characters"
                                    maxlength="32"
                                    aria-describedby="code-hint"
                                    required
                                    autofocus
                                >
                            </div>
                            <p class="admin-login__field-hint" id="code-hint">Masukkan kode tanpa spasi. Recovery code memakai tanda hubung.</p>
                            @error('code')
                                <p class="admin-login__field-error" id="code-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="admin-login__submit" type="submit">Verifikasi dan Masuk</button>
                    </form>

                    <a class="admin-login__back" href="{{ route('login') }}">Kembali ke Login</a>
                </div>
            </section>
        </main>
    </body>
</html>
