<section class="py-8 md:py-14" aria-labelledby="bestsellers-title">
    <div class="section-heading">
        <h2 id="bestsellers-title">Buku bestseller</h2>
        <p>Pilihan yang ditandai oleh Alfikar.</p>
    </div>
    @if ($bestsellers->isNotEmpty())
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 md:gap-6 xl:grid-cols-4">
            @foreach ($bestsellers as $product)<x-book-card :product="$product" />@endforeach
        </div>
    @else
        <p class="border border-rule bg-white p-5 text-sm text-ink/70">Belum ada buku yang ditandai sebagai bestseller.</p>
    @endif
</section>
