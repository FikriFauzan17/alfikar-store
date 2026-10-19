@extends('layouts.admin')

@section('title', 'Statistik katalog')

@section('content')
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Statistik agregat</p>
            <h1 class="font-display text-3xl text-brand-dark">Kunjungan produk &amp; klik WhatsApp</h1>
            <p class="admin-page-heading__description">Klik dihitung saat aplikasi mengalihkan ke WhatsApp, bukan saat pesan terkirim. Angka bukan pesanan atau penjualan; deduplikasi maksimal sekali per produk, sesi, dan jenis aktivitas setiap {{ $deduplicateMinutes }} menit. Data diringkas per hari dan dihapus setelah {{ $retentionDays }} hari. Crawler atau sesi baru dapat memengaruhi hitungan.</p>
        </div>
    </div>

    <form class="admin-section mb-5 flex flex-wrap items-end gap-4" method="GET" action="{{ route('admin.statistics.products') }}">
        <div>
            <label class="admin-field-label" for="statistics-days">Rentang waktu</label>
            <select class="admin-field-input" id="statistics-days" name="days">
                @foreach ([7, 30, 90, 365] as $range)
                    <option value="{{ $range }}" @selected($days === $range)>{{ $range }} hari</option>
                @endforeach
            </select>
        </div>
        <button class="admin-button admin-button--primary" type="submit">Terapkan</button>
    </form>

    <section class="admin-stat-grid mb-6" aria-label="Total aktivitas katalog">
        <div class="admin-stat-card">
            <span class="admin-stat-card__label">Kunjungan produk</span>
            <strong>{{ number_format($totals['productViews']) }}</strong>
            <span class="admin-stat-card__note">Sejak {{ \Illuminate\Support\Carbon::parse($startDate)->translatedFormat('j M Y') }}</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-card__label">Klik WhatsApp</span>
            <strong>{{ number_format($totals['whatsappClicks']) }}</strong>
            <span class="admin-stat-card__note">Tidak mengonfirmasi pemesanan atau penjualan</span>
        </div>
    </section>

    @if ($metrics->isNotEmpty())
        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption class="sr-only">Statistik aktivitas produk selama {{ $days }} hari</caption>
                <thead>
                    <tr><th scope="col">Produk</th><th scope="col">Kunjungan</th><th scope="col">Klik WhatsApp</th></tr>
                </thead>
                <tbody>
                    @foreach ($metrics as $metric)
                        <tr>
                            <th scope="row">{{ $metric->product?->title ?? 'Produk dihapus' }}</th>
                            <td>{{ number_format($metric->product_views) }}</td>
                            <td>{{ number_format($metric->whatsapp_clicks) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $metrics->links() }}</div>
    @else
        <div class="admin-section text-center">
            <h2 class="font-display text-xl text-brand-dark">Belum ada aktivitas tercatat</h2>
            <p class="mt-2 text-sm text-ink/70">Ringkasan akan muncul setelah pengunjung membuka halaman produk atau memilih WhatsApp.</p>
        </div>
    @endif
@endsection
