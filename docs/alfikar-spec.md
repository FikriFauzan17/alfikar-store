# Spesifikasi Toko Buku Alfikar

## Tujuan dan batasan

Bangun website profil dan katalog Toko Buku Alfikar di workspace Laravel yang sudah ada. Pertahankan stack; jangan mengganti framework. Referensi visual: https://ibnazka.com/. Gunakan referensi hanya untuk memandu komposisi dan hierarki. Jangan menyalin logo, teks, foto, klaim layanan, data penjualan, atau konten lain milik referensi. Tidak ada screenshot pengguna yang tersedia pada konteks saat spesifikasi ini disimpan.

Tidak ada akun pelanggan, registrasi publik, keranjang, checkout, payment gateway, atau pesanan otomatis. Seluruh transaksi berlangsung lewat WhatsApp. Jangan deploy, menimpa `.env`, menghapus data, atau membuat migration destruktif.

## Tampilan dan navigasi

Arah visual: modern Islamic; emerald `#047857`, hijau tua `#064E3B`, ivory `#FAF9F6`, emas `#C6A75E`; motif halus, tipografi terbaca, ruang kosong cukup, aksesibel, dan responsif. Beranda: header/navigasi, hero buku unggulan, grid kategori, informasi layanan, sorotan bestseller, grid buku, artikel terbaru, footer. Menu: Beranda, Produk, Tentang, Artikel, Kontak. Gunakan komponen reusable dan aset ringan.

## Halaman publik

- Beranda dengan konten unggulan yang dikelola admin.
- Katalog dengan pencarian, filter, sorting allowlist, dan pagination.
- Detail produk dengan galeri foto maksimal lima gambar.
- Profil/tentang, daftar artikel, detail artikel, dan kontak.
- Detail produk menampilkan identitas, harga/stok, deskripsi, spesifikasi, breadcrumb, dan tindakan WhatsApp sesuai kondisi stok.

## Data dan administrasi

Produk mempunyai kode dan slug unik, judul, penulis, penerbit, ISBN opsional, kategori, deskripsi, spesifikasi, stok, harga satuan dan grosir, minimum pembelian grosir, status publikasi, penanda unggulan/bestseller, dan maksimal lima foto. Bestseller dipilih admin, tidak disimpulkan atau dikarang dari angka penjualan.

Admin privat mengelola kategori, produk/foto, artikel, banner, profil, kontak/nomor WhatsApp/sosial, dan SEO. Semua route, aksi, dan resource admin wajib autentikasi serta otorisasi server. Tanpa registrasi publik dan tanpa password bawaan/default.

Artikel mendukung draft, publikasi terjadwal, gambar, ringkasan, isi rich text yang disanitasi dengan allowlist, dan metadata SEO.

## WhatsApp

Nomor berasal dari pengaturan dan divalidasi dalam format internasional; gunakan domain tetap `https://wa.me/`. Pesan ter-URL-encode memuat judul, kode produk, jumlah, harga yang berlaku, subtotal, URL produk, serta permintaan konfirmasi stok dan ongkir. Jumlah harus integer minimal satu. Harga grosir berlaku hanya bila jumlah memenuhi minimum grosir. Subtotal tidak termasuk ongkir dan harus dikonfirmasi. Untuk stok habis, tawarkan pesan untuk menanyakan ketersediaan. Nomor invalid menonaktifkan tombol. Klik WhatsApp tidak mengubah stok atau data.

## Keamanan

Gunakan autentikasi admin dan hashing password framework; tanpa register publik atau kredensial default. Terapkan rate limit login berbasis akun/IP dan endpoint mahal, respons login generik, regenerasi/invalidation session, timeout, serta cookie produksi Secure/HttpOnly/SameSite. Semua mutasi memakai CSRF dan metode non-GET; validasi server-side, query berparameter, allowlist field/filter/sort, escaping output, dan proteksi mass assignment, IDOR, XSS, SQL injection, traversal, dan open redirect.

Upload hanya JPEG/PNG/WebP dengan batas default 5 MB dan 20 megapiksel per gambar (dapat dikonfigurasi); validasi isi/MIME/ukuran/dimensi/jumlah, nama acak, encode ulang, direktori non-executable, dan penghapusan hanya file milik resource terkait. Proses satu gambar per satu gambar; validasi dimensi sebelum decode penuh bila memungkinkan, batasi sumber daya decoder dan dimensi encoding, koreksi orientasi, hapus metadata, encode ulang ke WebP. Original tidak disimpan secara default; jangan simpan base64 di database.

Validasi URL tanpa menerima HTML/iframe bebas dan tanpa fetch URL pengguna (hindari SSRF). Simpan rahasia dalam environment, bukan Git/log/public; produksi debug mati, document root `public`, hak DB minimum, HTTPS, dan permission tanpa 777. Terapkan CSP yang sesuai integrasi, `frame-ancestors`, `nosniff`, Referrer-Policy, serta HSTS setelah HTTPS siap; batasi CORS bila perlu. Audit log tanpa rahasia, konfirmasi penghapusan, backup privat dan prosedur restore, serta audit dependensi. Dokumentasikan kebutuhan hosting/WAF/DDoS dan risiko yang masih tersisa; jangan mengklaim kebal serangan.

## Gambar dan performa

Batas upload default 5 MB dan 20 MP harus dapat dikonfigurasi serta diselaraskan dengan batas aplikasi dan server. Buat varian lebar 320 px, 640 px, dan detail maksimal 1200 px tanpa upscale; kualitas awal 75–80 dengan batas minimum keterbacaan. Target ukuran thumbnail 15–40 KB, kartu 40–90 KB, detail 100–200 KB sebagai sasaran, bukan batas mutlak. Gunakan `srcset`/`sizes`, dimensi `width`/`height`, lazy-load di bawah fold, dan jangan lazy-load hero utama. Versikan aset dan gunakan cache panjang; jangan encode ulang per request.

Gunakan pagination, pilih kolom relevan, eager loading, indeks yang sesuai, dan cache publik dengan invalidasi pada perubahan admin. Jangan cache halaman/session admin sebagai publik. Hindari library besar, N+1, dan carousel berat. Target Core Web Vitals: LCP <= 2,5 detik, INP <= 200 ms, CLS <= 0,1. Ukur dan laporkan kondisi pengujian; jangan mengklaim tercapai tanpa bukti.

## SEO

HTML SSR yang dapat dirayapi, slug stabil, title/description unik, canonical, Open Graph, alt, heading semantik, dan breadcrumb. Sitemap hanya berisi konten publik terbit; robots sesuai; 404 benar; redirect 301 untuk slug lama. Structured data Organization/Book/Product/Article/BreadcrumbList hanya berdasarkan data nyata. Jangan membuat rating palsu atau menjanjikan posisi Google. Cegah ledakan indeks dari filter/sort menggunakan canonical/noindex sesuai fungsi.

## Lingkungan lokal dan hosting

Target lokal Windows/Laragon di workspace aktual atau `C:\\laragon\\www\\alfikar`. Sesuaikan dengan PHP dan MySQL/MariaDB Laragon yang aktif; verifikasi versinya sebelum perubahan lingkungan. Untuk Laravel, arahkan document root virtual host ke `public`.

Konfigurasi lokal melalui `.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=alfikar`, `DB_USERNAME`/`DB_PASSWORD` sesuai Laragon. Sesuaikan host/port bila berbeda. Jangan mengasumsikan password kosong atau menimpa `.env`. Buat database `alfikar` hanya jika belum ada dan jangan menghapus database lain. Pisahkan konfigurasi lokal dari produksi; jangan bawa kredensial database lokal ke hosting. Dokumentasikan cara menjalankan, build aset, migration, dan membuat admin.

## Stack terdeteksi

Workspace sudah berisi Laravel 13 (`laravel/framework ^13.17`), batas PHP Composer `^8.3`, Blade, Vite, dan Tailwind CSS 4. Pemeriksaan executable Laragon menemukan PHP CLI 8.3.33 dan klien MySQL 8.4.3. Versi server DB yang sedang berjalan belum diverifikasi. `.env` tidak dibaca atau diubah. Terminal tidak menemukan PHP/MySQL lewat `PATH`; executable Laragon ditemukan di `C:\\laragon\\bin`. Workspace saat ini tidak terdeteksi sebagai Git repository.
