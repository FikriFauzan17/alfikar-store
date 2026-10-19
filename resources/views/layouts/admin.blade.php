<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#064E3B">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title', 'Administrasi') | Toko Buku Alfikar</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-layout">
        <div class="admin-shell">
            <aside class="admin-sidebar">
                <a class="admin-sidebar__brand" href="{{ route('admin.dashboard') }}" aria-label="Alfikar, ringkasan admin">
                    <span class="admin-sidebar__brand-mark" aria-hidden="true">A</span>
                    <span><strong>Alfikar</strong><small>ADMINISTRASI</small></span>
                </a>
                @can('access-admin')
                    <x-admin.navigation />
                @endcan
                <p class="admin-sidebar__note">Buku, ilmu, dan inspirasi.</p>
            </aside>

            <div class="admin-workspace">
                <header class="admin-topbar">
                    <details class="admin-mobile-menu">
                        <summary aria-label="Buka navigasi admin"><span aria-hidden="true">☰</span> Menu admin</summary>
                        @can('access-admin')
                            <x-admin.navigation />
                        @endcan
                    </details>
                    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
                        <a href="{{ route('admin.dashboard') }}">Ringkasan</a>
                        @unless (request()->routeIs('admin.dashboard'))
                            <span aria-hidden="true">/</span>
                            <span aria-current="page">{{ trim($__env->yieldContent('title')) ?: 'Administrasi' }}</span>
                        @endunless
                    </nav>
                    <div class="admin-topbar__account">
                        <span class="admin-topbar__user">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="admin-button admin-button--quiet" type="submit">Keluar</button>
                        </form>
                    </div>
                </header>

                <main id="admin-content" class="admin-content">
                    @if (session('status'))
                        <div class="admin-alert admin-alert--success" role="status">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="admin-alert admin-alert--error" role="alert">
                            <p class="font-semibold">Periksa kembali data yang dikirim.</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>