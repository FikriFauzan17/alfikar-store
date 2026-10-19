# Keputusan Visual Alfikar

## Arah desain

- Modern Islamic: emerald `#047857`, hijau tua `#064E3B`, ivory `#FAF9F6`, emas `#C6A75E`; gunakan emas sebagai aksen, bukan bidang dominan.
- Motif geometris halus pada bidang terbatas, latar ivory, ruang kosong lega, kontras kuat, dan fokus keyboard terlihat.
- Display serif lokal (Palatino/Georgia) untuk karakter editorial; sans sistem untuk navigasi dan isi agar ringan dan terbaca.
- Gunakan foto sampul Alfikar yang sah saat tersedia. Tahap 1 tidak menambah aset sampul atau data produk.

## Peta Beranda

Urutan: header/navigasi -> hero buku unggulan -> grid kategori -> informasi layanan -> sorotan bestseller -> grid buku -> artikel terbaru -> footer.

Header: Beranda, Produk, Tentang, Artikel, Kontak. Desktop satu baris; mobile wordmark dan kontrol menu tetap ringkas, nav dibuka dengan kontrol native yang dapat keyboard. Jangan tampilkan login pelanggan, pencarian global, atau cart.

Hero: bidang terpusat dengan maksimum lebar sekitar 1280 px; desktop dua kolom dengan teks kiri dan cover asli di kanan; mobile judul dan cover bertumpuk/overlap tanpa mengurangi keterbacaan. Jangan gunakan placeholder sebagai buku sungguhan.

Grid: kategori 3 kolom desktop dengan jarak antarkartu sekitar 16-24 px; menjadi 1 kolom mobile seperti ritme referensi. Sorotan bestseller merupakan area editorial terpisah dari grid buku. Grid buku direncanakan 4 kolom desktop besar, 2-3 kolom tablet, dan 2 kolom mobile; pertahankan rasio cover serta ruang judul/harga konsisten. Layanan diringkas sebagai baris fitur, bukan klaim sebelum datanya tersedia. Artikel terbaru berupa grid sederhana; hindari carousel.

Spacing: gutters desktop sekitar 48-80 px sesuai lebar viewport dan maksimum konten; mobile 16 px. Jeda antarseksi desktop 56-72 px dan mobile 40-48 px. Radius kartu maksimal 8 px. Target hero desktop sekitar 0.48-0.52 kali lebar bidang; mobile sekitar 230-280 px tinggi. Nilai ini panduan visual, bukan ukuran piksel pasti.

## Hasil Inspeksi Referensi

Situs dibuka pada viewport browser 1440x960 dan 390x844. Screenshot berhasil diperiksa pada kedua ukuran setelah scroll ke atas.

- Desktop: header horizontal tipis; konten memakai sekitar 90% lebar viewport. Hero hijau besar, mendekati rasio 2:1, dengan teks kiri dan visual cover/portrait kanan. Kartu kategori mulai tepat di bawah hero dengan jarak sempit dan lebar kartu tidak seragam (termasuk satu kartu lebih lebar). Halaman berlanjut vertikal.
- Mobile: header menampilkan brand di kiri dan kontrol ringkas di kanan. Hero hampir selebar layar dengan tinggi sekitar seperempat viewport; teks berada di kiri dan cover menumpuk di area tengah/kanan. Kartu kategori menjadi satu kolom, bidang gelap lebar, cover menonjol/overlap ke kanan atas. Konten berlanjut dengan scroll vertikal.
- Proporsi merupakan perkiraan visual; screenshot browser diskalakan. Posisi hero/kategori dan perubahan grid desktop-mobile terlihat jelas.
- Referensi memuat logo, foto, teks, klaim, tombol, serta kontrol login/cart. Tidak satu pun disalin. Screenshot lampiran pengguna tidak tersedia pada percakapan ini.

## Implementasi Tahap 1

Tema CSS/Tailwind v4 mempunyai token warna/font, fokus keyboard, dan breakpoint 700 px. Shell Blade SSR menggunakan header/footer reusable, navigasi desktop, serta menu native yang toggle di mobile. Hero saat ini hanya memiliki copy Alfikar yang netral; kategori, cover, layanan, bestseller, produk, dan artikel belum dibuat karena data autentik belum tersedia. Hasil mengikuti hierarki dan ritme umum, bukan salinan piksel atau konten referensi.
