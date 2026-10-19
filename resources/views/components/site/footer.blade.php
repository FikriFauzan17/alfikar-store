@props(['siteName' => 'Toko Buku Alfikar', 'informationPages' => []])

<footer class="site-footer">
    <div class="site-footer__inner">
        <div>
            <p class="site-footer__name">{{ $siteName }}</p>
            <p class="site-footer__note">Buku, profil toko, dan kabar terbaru.</p>
            <nav class="mt-3 flex flex-wrap gap-4 text-sm" aria-label="Informasi kebijakan">
                @foreach ($informationPages as $slug => $title)
                    <a class="underline underline-offset-4" href="{{ route('pages.information', ['page' => $slug]) }}">{{ $title }}</a>
                @endforeach
            </nav>
        </div>
        <p class="site-footer__copyright">&copy; {{ now()->year }} {{ $siteName }}</p>
    </div>
</footer>