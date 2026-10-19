<nav class="admin-sidebar__nav" aria-label="Navigasi admin">
    <a class="admin-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 10.5 12 3l8.5 7.5v9a1 1 0 0 1-1 1h-5v-6h-5v6h-5a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/></svg>
        <span>Ringkasan</span>
    </a>
    @can('access-admin')
        <a class="admin-sidebar__link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}" @if (request()->routeIs('admin.users.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="1.5"/><path d="M3 20a6 6 0 0 1 12 0m4-10v7m-3.5-3.5h7" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"/></svg>
            <span>Pengguna</span>
        </a>
        <a class="admin-sidebar__link {{ request()->routeIs('admin.statistics.*') ? 'is-active' : '' }}" href="{{ route('admin.statistics.products') }}" @if (request()->routeIs('admin.statistics.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19.5h16M6.5 16V9m5 7V4.5m5 11.5v-5" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"/></svg>
            <span>Statistik katalog</span>
        </a>
        <a class="admin-sidebar__link {{ request()->routeIs('admin.information-pages.*') ? 'is-active' : '' }}" href="{{ route('admin.information-pages.index') }}" @if (request()->routeIs('admin.information-pages.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3.5h9l4 4v13H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Zm8 1v4h4m-9 4h6m-6 4h6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/></svg>
            <span>Halaman informasi</span>
        </a>
    @endcan
    <a class="admin-sidebar__link {{ request()->routeIs('admin.security.*') ? 'is-active' : '' }}" href="{{ route('admin.security.index') }}" @if (request()->routeIs('admin.security.*')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 7.8-7 10-4.2-2.2-7-5.5-7-10V6l7-3Z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/></svg>
        <span>Keamanan akun</span>
    </a>
    <a class="admin-sidebar__link {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}" @if (request()->routeIs('admin.categories.*')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5.5h7v6H4v-6Zm9 0h7v6h-7v-6ZM4 13.5h7v5H4v-5Zm9 0h7v5h-7v-5Z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"/></svg>
        <span>Kategori</span>
    </a>
    <a class="admin-sidebar__link {{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}" @if (request()->routeIs('admin.products.*')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4.5h14v15H5v-15Zm4 0v15m3-11h4m-4 4h4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/></svg>
        <span>Produk</span>
    </a>
    <a class="admin-sidebar__link {{ request()->routeIs('admin.articles.*') ? 'is-active' : '' }}" href="{{ route('admin.articles.index') }}" @if (request()->routeIs('admin.articles.*')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3.5h9l4 4v13H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Zm8 1v4h4m-9 4h6m-6 4h6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/></svg>
        <span>Artikel</span>
    </a>
    <a class="admin-sidebar__link {{ request()->routeIs('admin.banners.*') ? 'is-active' : '' }}" href="{{ route('admin.banners.index') }}" @if (request()->routeIs('admin.banners.*')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="m5 16 4.5-4.5 3 3 2.5-2.5 4 4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/></svg>
        <span>Banner</span>
    </a>
    <a class="admin-sidebar__link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}" @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5v2m0 13v2m8.5-8.5h-2m-13 0h-2m14.51-6.01-1.41 1.41m-9.2 9.2-1.41 1.41m12.02 0-1.41-1.41m-9.2-9.2L5.99 5.99M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"/></svg>
        <span>Pengaturan situs</span>
    </a>
</nav>
