<section class="py-8 md:py-14" aria-labelledby="books-title">
    <div class="section-heading flex flex-wrap items-end justify-between gap-4">
        <div><h2 id="books-title">Katalog buku</h2><p>Pilihan buku yang tersedia di Alfikar.</p></div>
        <a class="text-sm text-brand underline underline-offset-4" href="{{ route('products.index') }}">Lihat semua produk</a>
    </div>
    @if ($products->isNotEmpty())
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 md:gap-6 xl:grid-cols-4">
            @foreach ($products as $product)<x-book-card :product="$product" />@endforeach
        </div>
    @else
        <p class="border border-rule bg-white p-5 text-sm text-ink/70">Belum ada produk yang diterbitkan.</p>
    @endif
</section>
