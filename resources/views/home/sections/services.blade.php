<section class="py-8 md:py-14" aria-labelledby="services-title">
    <div class="section-heading"><h2 id="services-title">Informasi layanan</h2></div>
    @php($serviceLabels = ['service_delivery' => 'Pengiriman', 'service_payment' => 'Pembayaran', 'service_returns' => 'Pengembalian', 'service_hours' => 'Jam layanan'])
    @php($availableServices = array_filter($serviceLabels, fn ($key) => filled($services[$key] ?? null), ARRAY_FILTER_USE_KEY))
    @if ($availableServices !== [])
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($availableServices as $key => $label)
                <div class="border-t-2 border-gold bg-white px-4 py-4">
                    <h3 class="font-semibold text-brand-dark">{{ $label }}</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-ink/70">{{ $services[$key] }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p class="border border-rule bg-white p-5 text-sm text-ink/70">Informasi layanan belum tersedia.</p>
    @endif
</section>
