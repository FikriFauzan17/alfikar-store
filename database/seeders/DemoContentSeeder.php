<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'contoh-buku'],
            [
                'name' => 'Contoh Buku',
                'description' => 'Kategori contoh untuk menampilkan katalog demo.',
                'sort_order' => 1,
                'is_published' => true,
            ],
        );

        $products = [
            [
                'code' => 'DEMO-BK-001',
                'slug' => 'contoh-adab-menuntut-ilmu',
                'title' => 'Contoh: Adab Menuntut Ilmu',
                'author' => 'Tim Penulis Contoh',
                'publisher' => 'Penerbit Contoh',
                'description' => 'Data produk demo untuk menampilkan katalog. Ganti deskripsi, harga, dan stok ini dengan informasi produk yang sebenarnya sebelum toko dipublikasikan.',
                'specifications' => ['Format' => 'Contoh', 'Halaman' => '120 halaman (data contoh)'],
                'stock' => 12,
                'unit_price' => 85000,
                'wholesale_price' => 75000,
                'wholesale_minimum' => 10,
                'is_featured' => true,
                'is_bestseller' => true,
            ],
            [
                'code' => 'DEMO-BK-002',
                'slug' => 'contoh-panduan-membaca-al-quran',
                'title' => 'Contoh: Panduan Membaca Al-Qur\'an',
                'author' => 'Tim Penulis Contoh',
                'publisher' => 'Penerbit Contoh',
                'description' => 'Contoh entri katalog dengan harga dan stok ilustratif. Pastikan seluruh detail bibliografi dan ketersediaan sudah benar sebelum digunakan untuk penjualan.',
                'specifications' => ['Format' => 'Contoh', 'Halaman' => '96 halaman (data contoh)'],
                'stock' => 8,
                'unit_price' => 74000,
                'wholesale_price' => 65000,
                'wholesale_minimum' => 10,
                'is_featured' => true,
                'is_bestseller' => false,
            ],
            [
                'code' => 'DEMO-BK-003',
                'slug' => 'contoh-kisah-teladan-anak',
                'title' => 'Contoh: Kisah Teladan untuk Anak',
                'author' => 'Tim Penulis Contoh',
                'publisher' => 'Penerbit Contoh',
                'description' => 'Produk contoh untuk pratinjau tampilan katalog. Ganti dengan data buku, harga, stok, dan foto yang telah diverifikasi.',
                'specifications' => ['Format' => 'Contoh', 'Halaman' => '80 halaman (data contoh)'],
                'stock' => 5,
                'unit_price' => 92000,
                'wholesale_price' => 80000,
                'wholesale_minimum' => 10,
                'is_featured' => false,
                'is_bestseller' => false,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->firstOrCreate(
                ['slug' => $product['slug']],
                [
                    ...$product,
                    'category_id' => $category->id,
                    'is_published' => true,
                    'published_at' => now()->subDays(3),
                    'meta_title' => $product['title'],
                    'meta_description' => $product['description'],
                ],
            );
        }

        $articles = [
            [
                'title' => 'Contoh: Tips Merawat Buku agar Tetap Awet',
                'slug' => 'contoh-tips-merawat-buku',
                'summary' => 'Artikel demo tentang kebiasaan sederhana untuk menjaga koleksi buku tetap rapi dan nyaman dibaca.',
                'body' => '<p>Ini adalah artikel contoh untuk pratinjau situs Alfikar. Ganti isi demo ini dengan artikel yang sudah diperiksa sebelum situs dipublikasikan.</p><h2>Simpan di tempat yang kering</h2><p>Jauhkan buku dari air dan kelembapan. Rak yang bersih dan memiliki sirkulasi udara membantu menjaga koleksi tetap rapi.</p><h2>Gunakan dengan hati-hati</h2><p>Tangan yang bersih dan pembatas halaman membantu menjaga kondisi buku selama digunakan.</p>',
                'meta_title' => 'Contoh: Tips Merawat Buku agar Tetap Awet',
                'meta_description' => 'Artikel demo Alfikar tentang perawatan buku. Ganti konten ini dengan informasi yang sudah diverifikasi.',
            ],
            [
                'title' => 'Contoh: Membangun Kebiasaan Membaca di Rumah',
                'slug' => 'contoh-kebiasaan-membaca-di-rumah',
                'summary' => 'Contoh artikel untuk mengisi bagian artikel terbaru dan menunjukkan tampilan halaman detail.',
                'body' => '<p>Konten ini disediakan sebagai contoh tampilan artikel dan belum dimaksudkan sebagai panduan ahli.</p><h2>Mulai dari waktu yang nyaman</h2><p>Sediakan waktu membaca yang singkat dan rutin sesuai kegiatan keluarga.</p><h2>Pilih bacaan bersama</h2><p>Diskusikan bacaan yang diminati, lalu sesuaikan pilihan dengan usia dan kebutuhan pembaca.</p>',
                'meta_title' => 'Contoh: Membangun Kebiasaan Membaca di Rumah',
                'meta_description' => 'Artikel demo tentang kebiasaan membaca di rumah. Ganti dengan konten final sebelum publikasi.',
            ],
        ];

        foreach ($articles as $article) {
            Article::query()->firstOrCreate(
                ['slug' => $article['slug']],
                [
                    ...$article,
                    'status' => ArticleStatus::Published,
                    'published_at' => now()->subDays(2),
                ],
            );
        }
    }
}
