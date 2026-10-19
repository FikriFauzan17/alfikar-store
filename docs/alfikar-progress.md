# Progres Alfikar

## Posisi saat ini

- [x] Menerima brief awal dan menyimpannya dalam `docs/alfikar-spec.md`.
- [x] Mengidentifikasi stack: starter Laravel 13, Blade/Vite, Tailwind CSS 4.
- [x] Memeriksa referensi pada desktop 1440x960 dan mobile 390x844; peta section/proporsi disimpan dalam `docs/alfikar-layout.md`.
- [x] Membuat shell SSR, tema warna/type, serta komponen header/footer tanpa data produk palsu.
- [x] Memverifikasi Vite build, route `/`, dan feature test homepage.
- [x] Memverifikasi nav desktop terlihat dan menu mobile closed/open pada viewport 390 px.
- [x] Tahap 1 selesai; berhenti menunggu instruksi tahap berikutnya.
- [x] Tahap 2: schema, auth admin, dan audit dasar dibuat; test berjalan pada SQLite in-memory.
- [x] Database default aktif dikonfirmasi SQLite; tiga migration starter tercatat applied.
- [x] Tidak membuat akun admin default; pembuatan pertama melalui command interaktif.
- [x] Tahap 3: processor upload WebP reusable, batas resource, varian, cleanup, dan test.
- [x] Tahap 4: CRUD admin kategori/produk, galeri, harga/stok/publikasi, audit, konfirmasi hapus, dan test.
- [x] Tahap 5: halaman publik SSR, katalog/galeri, profil/kontak/artikel, WhatsApp stateless, dan SEO dasar.
- [x] Tahap 6: CRUD artikel/banner, rich text tersanitasi, draft/jadwal, profil/kontak/WA/sosial/peta/SEO, invalidasi cache, dan test.
- [x] Memulihkan error HTTP 500 beranda: setelah mengonfirmasi SQLite aktif dan membuat backup, seluruh migration Alfikar yang aditif diterapkan tanpa menjalankan seeder.
- [ ] Kompatibilitas migration pada MySQL Laragon belum diverifikasi; tidak ada konfigurasi `.env` atau database MySQL yang diubah.

Database SQLite aktif dibackup ke `%TEMP%` sebelum migration diterapkan. Tidak ada data yang dihapus, seeder dijalankan, atau `.env`/konfigurasi lingkungan diubah. Workspace tidak terdeteksi sebagai Git repository, jadi `git status` tidak tersedia.

## Tahap 2

### Peta Database Existing

Koneksi default Laravel/Boost adalah SQLite. Tabel yang ada: `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, dan `migrations`. Migration starter users/cache/jobs berada pada batch 1. Boost schema dan `artisan migrate:status` setelah pekerjaan tetap menunjukkan database persisten tidak berubah.

### Schema Tambahan

Migration aditif membuat `categories`, `products`, `product_images`, `articles`, `banners`, `site_settings`, `audit_logs`, dan menambah `users.is_admin` default false. Ada unique kode/slug/ISBN, FK dengan perilaku hapus terkontrol, indeks listing/status, harga/stok integer unsigned, varian gambar JSON (bukan file/base64), status artikel draft/terjadwal/terbit, serta field SEO. Relasi model mencakup kategori-produk-foto, artikel-penulis, setting-pengubah, dan audit-aktor.

Tidak ada sample katalog. `is_bestseller` hanya flag boolean admin, bukan hitungan/hasil penjualan. Processor gambar reusable tersedia, tetapi endpoint CRUD/upload, validasi request CRUD, sanitasi rich text, dan allowlist URL belum dibuat.

### Akses Admin

Admin memakai tabel `users` yang sudah ada dengan `is_admin` default false dan tidak mass assignable. GET/POST `/admin/login` tidak menyediakan register/reset publik; POST login dibatasi 5 percobaan per email dan 20 per IP per menit, memakai pesan kredensial generik, redirect tetap internal, serta regenerasi session. Dashboard/logout dilindungi `auth` dan gate `access-admin`; logout POST meng-invalidate session/regenerasi CSRF token. Form memakai middleware `web` dan `@csrf`. Cookie HttpOnly/SameSite mengikuti konfigurasi Laravel; Secure default aktif pada production. Session idle default 120 menit.

`php artisan app:make-admin` meminta nama/email dan password tersembunyi minimal 12 karakter beserta konfirmasi; password di-hash dan aksi tercatat tanpa menyimpan rahasia. `DatabaseSeeder` tidak membuat akun. Factory password bawaan hanya fixture test.

## Runtime dan Verifikasi

- Laragon menyediakan PHP 8.3.33 dan klien MySQL 8.4.3. PHP 8.3 tidak dapat menjalankan aplikasi karena dependency terkunci meminta PHP `>=8.4.1`, meski `composer.json` mendeklarasikan `^8.3`.
- PHP sistem `C:\\php\\php.exe` 8.5.10 digunakan sementara untuk `artisan serve` dan pengujian; aplikasi berjalan di `http://localhost:8000`. Instalasi Laragon dan dependency tidak diubah.
- `npm run build` lulus. Plugin font Vite memberi peringatan opsional bahwa `fontaine` tidak terpasang; build tetap berhasil.
- `php artisan test --compact tests/Feature/ExampleTest.php`: 1 test lulus. Browser membuka `/` dan mengonfirmasi layout SSR. Menu diuji pada viewport 1440 px dan 390 px.
- `php artisan test --compact`: 18 tes lulus, 95 assertion; mencakup migration/relasi/unique/FK, draft/terjadwal, akses admin, rate limit akun/IP, session, redirect validation, seeder, dan command setup.
- Pada akhir Tahap 2, migration Alfikar hanya dijalankan pada SQLite `:memory:` lewat test; sembilan migration masih `Pending` pada DB persisten. Status aktual setelah perbaikan Tahap 5 dicatat pada bagian Tahap 5 di bawah.
- `composer audit`: 0 advisory; `npm audit`: 0 vulnerability. Pint dijalankan pada file PHP yang berubah; opsi `--dirty` tidak tersedia tanpa Git repository.
- Route `/admin` tercatat dengan middleware `web`, `auth`, dan `can:access-admin`; login POST dengan `web` dan `throttle:admin-login`.
- Test database memakai SQLite in-memory. Kompatibilitas migration di MySQL Laragon belum dijalankan karena koneksi aplikasi aktif SQLite dan server MySQL belum dipastikan.
- Core Web Vitals/Lighthouse belum diukur; tidak ada klaim target performa tercapai.

## Tahap 3

### Processor Gambar

`App\Services\ProductImageProcessor::storeFor()` memproses satu `UploadedFile` per panggilan. Ia memeriksa upload status, ukuran byte, MIME via Fileinfo dan signature/dimensi via `getimagesize()` sebelum decode; hanya JPEG/PNG/WebP diterima. Batas default config: 5 MiB, 20 MP, dimensi sisi maksimal 12.000 px, serta estimasi resource decoder dan batas memori proses.

GD mendecode, koreksi EXIF JPEG orientation, lalu membuat WebP pada lebar 320/640/1200 px yang tidak melebihi sumber. Canvas/varian dibuat berurutan dan dilepas; kualitas mulai 78, adaptif terhadap target byte 40/90/200 KiB, minimum 64. Re-encode membuang metadata. Original tidak disimpan; DB hanya menyimpan disk/path varian, alt, dimensi, MIME, ukuran total, dan sort order.

Batas lima foto menghitung record existing, dicek ulang dalam transaksi dan dipasangkan dengan unique `(product_id, sort_order)`. Nama file memakai UUID. Kegagalan storage/insert membersihkan path yang baru dibuat; cleanup gagal hanya mencatat product id dan jumlah file, tanpa payload/nama upload. Hapus hanya menerima path UUID WebP yang sesuai product id dan seluruh varian yang tersimpan.

### Verifikasi Gambar

- `vendor/bin/pest --compact tests/Feature/ProductImageProcessorTest.php` dengan PHP 8.5 dan opsi CLI `-d extension=gd -d extension=exif`: 8 tes, 73 assertion lulus.
- Suite penuh dengan extension yang sama: 26 tes, 168 assertion lulus. Dua warning dilaporkan Pest tanpa `warning_details`, juga muncul pada tes terisolasi; tidak ada test gagal.
- Tes mencakup file palsu, batas byte/pixel, foto ke-5/ke-6, output WebP terbaca dan ukuran target, no-upscale, EXIF rotation/stripping, cleanup decode gagal, traversal saat hapus, dan delta peak memory PHP di bawah 64 MiB untuk satu input 900x600.
- Laragon PHP 8.3.33 menyediakan GD/WebP/EXIF dan memory limit 512 MiB. PHP sistem 8.5 menyediakan DLL tersebut tetapi extension tidak aktif di konfigurasi default; tes memuatnya hanya lewat opsi `-d`, tanpa mengubah `php.ini`.
- PHP web runtime harus mengaktifkan GD dengan WebP dan EXIF untuk JPEG. Selaraskan `upload_max_filesize` minimal 5 MiB dan `post_max_size` di atas batas itu. Disk default `public`; `public/storage` symlink belum dibuat, jadi hosting perlu `storage:link` atau disk publik yang sesuai.
- Tidak ada endpoint CRUD upload yang dibuat di tahap ini. Tidak ada file gambar tersimpan ke storage persisten; seluruh upload tes memakai temporary fake disk.

## Tahap 4 Admin Katalog

Kategori dan produk sekarang memiliki halaman daftar terpaginated, form create/edit, dan layar konfirmasi sebelum delete. Product form mengelola identitas buku/kategori/ISBN, stok, harga satuan, harga grosir/minimum, spesifikasi JSON skalar, publikasi, featured, bestseller, waktu terbit, serta metadata SEO. Slug dihasilkan saat create dan tidak dapat diubah pada edit karena mekanisme redirect 301 slug lama belum dibuat.

Galeri memakai `ProductImageProcessor` yang sudah ada; foto utama adalah posisi urut 0. Upload request membatasi MIME/extension/ukuran dan menghitung foto existing; controller memproses setiap file satu per satu, membersihkan file/record batch sebelumnya jika langkah kemudian gagal, dan baru mencatat audit setelah batch lengkap. Reorder menerima himpunan ID yang tepat milik produk, menggunakan dua fase urutan untuk menghindari konflik unique, dan mengaudit perubahan. Hapus foto memerlukan halaman konfirmasi, route nested-scoped, pemeriksaan product ownership, validasi path UUID WebP, penghapusan varian milik resource, lalu normalisasi primary.

Mutasi dilindungi auth/gate admin, FormRequest allowlist, CSRF web, limiter upload per admin/IP, unique code/slug/ISBN, validasi cross-field harga grosir, serta audit event create/update/delete/upload/reorder tanpa nilai form/file. Penghapusan kategori yang masih direferensikan ditolak constraint FK. Produk menghapus foto terkait setelah konfirmasi.

`tests/Feature/AdminCatalogTest.php` mencakup akses guest/non-admin, CRUD kategori/produk, validasi field/unik/harga, upload palsu dan overflow, batch cleanup, urutan/foto utama, IDOR pada foto, audit, konfirmasi, dan file unrelated tetap aman. Suite penuh dengan PHP 8.5 + GD/EXIF CLI flags: 39 tes, 302 assertion lulus tanpa warning.

Pada verifikasi terbaru, sembilan migration Alfikar sudah diterapkan ke SQLite aktif, sehingga tabel kategori/produk/foto tersedia untuk halaman admin. `public/storage` symlink belum dibuat; URL preview foto pada disk public memerlukan storage publik disiapkan.

## Tahap 5 Publik dan WhatsApp

Beranda mengikuti urutan layout: hero buku unggulan, kategori, informasi layanan, sorotan bestseller, katalog buku, artikel terbaru, footer. Semua section membaca DB; setting layanan, kategori, bestseller dan artikel tidak diisi data rekaan. Kondisi kosong tampil bila belum ada konten. Grid responsif memakai cover `srcset`/`sizes`; hero tidak lazy-load, cover di bawah fold lazy-load.

Katalog `/produk` memiliki pencarian parameterized, filter kategori, sort allowlist (terbaru/judul/harga), pagination 12 item, dan eager loading kategori/foto. Query hanya memakai `Product::published()` (status terbit, waktu publikasi tercapai, kategori terbit); filter/sort yang memakai query string `noindex` dan canonical kembali ke katalog. Detail slug menampilkan galeri, spesifikasi, stok/harga satuan/grosir, breadcrumb, canonical/OG serta JSON-LD Book/Offer/BreadcrumbList. Draft/terjadwal tidak dapat dibuka publik.

Tentang/Kontak membaca hanya key `site_settings` yang di-allowlist dan `is_public=true`; tautan sosial harus URL HTTPS tervalidasi. Artikel daftar/detail hanya memakai `Article::published()`, escaped text, SEO canonical/OG, JSON-LD Article/BreadcrumbList. Home menyediakan JSON-LD Organization tanpa klaim tambahan.

WA menggunakan nomor E.164 dari setting publik, tombol nonaktif bila nomor invalid. GET `/produk/{slug}/whatsapp` memvalidasi integer quantity >=1 lalu redirect hanya ke `https://wa.me/` tetap dengan pesan ter-URL-encode: judul, kode, jumlah, harga yang berlaku (grosir saat minimum terpenuhi), subtotal belum ongkir, URL produk, dan permintaan konfirmasi stok/ongkir. Stok 0/kurang menghasilkan pertanyaan ketersediaan. Tidak ada cart, checkout, order record, atau mutasi stok.

`/sitemap.xml` mengalirkan URL canonical publik serta produk/artikel yang benar-benar terbit; query filter tidak dimasukkan. `/robots.txt` dinamis menunjuk sitemap dan mengecualikan admin/filter. Test memakai SQLite in-memory dan mencakup draft/terjadwal, filter/sort/pagination, empty states, detail/galeri, canonical/structured data, WA threshold/message/invalid number, dan stok tetap.

Full suite dengan PHP 8.5 + GD/EXIF CLI flags: 47 tes, 402 assertion lulus tanpa warning. Verifikasi lanjutan `AdminAuthenticationTest`, `PublicCatalogTest`, dan `SitemapTest`: 19 tes, 185 assertion lulus. `npm run build` lulus. Vite masih memberi peringatan opsional `fontaine` tidak terpasang. SQLite aktif sudah dibackup sebelum sembilan migration Alfikar diterapkan; tidak ada seed/data contoh atau penghapusan data. HTTP beranda kembali 200 dan menampilkan empty states yang sesuai. Server MySQL Laragon belum diverifikasi/digunakan. `public/storage` symlink belum dibuat. Core Web Vitals/Lighthouse belum diukur.

## Verifikasi referensi

Referensi `https://ibnazka.com/` diperiksa pada desktop dan mobile. Screenshot lampiran pengguna tidak tersedia. Layout Alfikar mengikuti hierarki umum saja, bukan identik; tidak ada aset atau konten referensi yang disalin.

## Tahap 6 Artikel dan Pengaturan

Admin kini dapat membuat, mengubah, menghapus, dan meninjau artikel dengan author admin otomatis, slug stabil saat edit, ringkasan, gambar/alt, SEO title/description, serta status draft/terjadwal/terbit. Waktu terjadwal wajib di masa depan; artikel berstatus Scheduled otomatis masuk daftar publik dan sitemap ketika waktunya tercapai, tanpa mengandalkan cron. Draft dan jadwal yang belum tercapai tetap 404 pada detail publik. Artikel lama maupun baru melewati sanitasi DOM allowlist saat ditampilkan; hanya elemen teks/heading/list/kutipan/kode/link terbatas yang dipertahankan, atribut dibuang kecuali `href`/`title` aman, dan script/style/iframe/object/embed/SVG/template/comment dibuang. Blade hanya merender hasil sanitasi; kolom judul, ringkasan, dan metadata tetap escaped.

Banner dapat dikelola melalui CRUD admin dengan status aktif, periode mulai/akhir, urutan, tautan HTTPS/path lokal, gambar, dan alt. Beranda hanya menampilkan satu banner aktif dalam periode. Gambar artikel/banner diproses WebP melalui `ProductImageProcessor` yang sama, disimpan pada direktori UUID per-resource, dan dibersihkan saat diganti/dihapus; original upload tidak disimpan.

Pengaturan admin mencakup nama/deskripsi/alamat profil, kontak/email/telepon, nomor WA E.164, tautan sosial HTTPS, URL Google Maps terbatas domain, metadata SEO default, dan informasi layanan homepage. Peta menjadi tautan Google Maps biasa, tidak di-fetch dan tidak menerima iframe. Data publik dibaca melalui cache 5 menit dan perubahan model maupun aksi settings admin menginvalidasi cache; nomor WhatsApp dibaca langsung agar perubahan DB eksternal juga segera memengaruhi validasi nomor. Nama usaha dipakai pada header/footer, halaman kontak/tentang, dan Organization structured data; SEO title/description menjadi fallback metadata.

Semua route admin berada di middleware `auth` dan gate `access-admin`; validasi memakai FormRequest allowlist, perubahan memakai metode non-GET/CSRF, event audit tanpa isi sensitif, serta halaman konfirmasi delete artikel/banner. Tidak ada migration atau dependency baru.

`tests/Feature/AdminContentTest.php` menambah 7 feature test untuk izin admin, CRUD artikel/banner, XSS saat simpan maupun render konten lama, draft/jadwal sebelum dan setelah waktu tayang, URL peta/WA/sosial/banner tidak valid, invalidasi cache, dan round-trip upload/penghapusan gambar dengan processor yang ada. Verifikasi akhir: `php -d extension=gd -d extension=exif vendor/bin/pest --compact` lulus 54 tes dan 493 assertion; `npm run build` lulus; Pint lulus pada seluruh file PHP yang diubah; pemeriksaan Problems tidak menemukan error pada file yang diperiksa. Build memberi peringatan opsional `fontaine` tidak terpasang. Pengujian memakai SQLite in-memory; kompatibilitas MySQL Laragon, storage link produksi, dan Core Web Vitals belum diverifikasi/diukur.

## Tahap 7 SEO dan Optimasi

### SEO, URL, dan Data Terstruktur

- Judul/deskripsi katalog dan halaman profil memakai nama usaha terkonfigurasi; daftar artikel memakai metadata halaman yang berbeda saat dipaginasi. Halaman artikel per-resource dan produk tetap menggunakan metadata tersimpan/fallback konten, canonical slug aktif, Open Graph, dan gambar Open Graph bila tersedia.
- Katalog dengan filter/query tetap `noindex,follow` dan canonical ke katalog bersih. Halaman artikel berhalaman memiliki canonical sendiri; parameter pelacakan/tidak dikenal dan nomor halaman tidak valid menjadi `noindex,follow`. Nomor halaman yang melampaui hasil artikel/produk menghasilkan 404.
- JSON-LD `Organization` berisi nama, URL, dan hanya deskripsi/email/telepon yang benar-benar dikonfigurasi. Detail artikel berisi headline, author/tanggal/publisher aktual, gambar opsional, serta URL halaman utama; detail buku berisi Book/Offer aktual, harga/stok/ISBN/gambar yang tersedia. Breadcrumb terlihat tetap dipertahankan dan juga tersedia sebagai `BreadcrumbList` pada halaman daftar serta detail.
- Slug artikel dan produk kini dapat diubah di admin. Migration aditif mencatat alias lama secara unik dengan FK cascade saat resource dihapus; URL lama memberi 301 hanya bila slug target saat ini masih publik, selain itu 404. Slug alias juga dilindungi dari penggunaan ulang oleh resource lain.
- Memperketat scope publik artikel agar record berstatus `published` dengan waktu publikasi masa depan tidak bocor ke halaman maupun sitemap; artikel `scheduled` tetap terbit otomatis tepat setelah jadwal tercapai. Sitemap/robots tetap mengalirkan hanya URL canonical publik dan melarang path admin/filter. Detail slug tidak dikenal serta draft/future tetap 404.

### Audit Query, Cache, Aset, dan Responsivitas

- Detail artikel sekarang mengambil data langsung dari slug dan detail produk tidak lagi melakukan binding lalu query ulang berdasarkan ID. Feature test query memverifikasi daftar 15 artikel memakai dua query artikel (count + page) dan satu query author, detail artikel satu query artikel, serta detail produk dua query produk (detail + rekomendasi), tanpa query author per-item.
- Query plan SQLite untuk sitemap produk memakai indeks `products_is_published_published_at_index` dan memakai temporary B-tree untuk urutan ID. Daftar artikel memakai indeks `articles_status_published_at_index` pada cabang published/scheduled dan temporary B-tree untuk urutan tanggal/ID. Jumlah produk/artikel/kategori pada database pengukuran adalah 0; tidak ada indeks tambahan selain unique index slug alias karena dataset kosong tidak cukup untuk membuktikan bottleneck pengurutan. Kompatibilitas/query plan MySQL belum diuji.
- Cache setting publik lima menit dan invalidasinya tidak diubah; regression test fase admin/settings tetap lulus. Tidak ada perubahan desain/CSS. Audit kode menemukan grid responsif yang telah ada serta `srcset`/`sizes`, dimensi gambar, lazy-load untuk konten bawah layar, dan prioritas untuk gambar hero; audit viewport baru maupun Core Web Vitals/Lighthouse tidak dilakukan.
- Build Vite menghasilkan CSS 69,76 kB (14,63 kB gzip), JS 0,15 kB (0,15 kB gzip), font WOFF2 16,86–17,40 kB per file. Build lulus; warning opsional `fontaine` yang belum terpasang tetap muncul.

### Pengukuran HTTP Lokal

Kondisi: PHP 8.5.10, Laravel 13.35.0, SQLite, `php artisan serve` di localhost, cache proses lokal, data katalog kosong; lima request `curl.exe` berurutan per endpoint, request pertama dikeluarkan lalu median dari empat request sisanya. Ukuran adalah byte body HTML/XML tanpa kompresi.

| Endpoint | Sebelum (median hangat) | Sesudah (median hangat) | Byte sebelum → sesudah |
|---|---:|---:|---:|
| `/` | 36,4 ms | 42,3 ms | 12.732 → 12.732 |
| `/produk` | 29,5 ms | 35,9 ms | 12.220 → 12.525 |
| `/artikel` | 23,1 ms | 30,4 ms | 10.299 → 10.608 |
| `/sitemap.xml` | 22,1 ms | 35,3 ms | 352 → 352 |

Pengukuran lokal bervariasi dan hasil waktu sesudah perubahan lebih lambat; ini bukan bukti regresi maupun peningkatan karena dataset kosong, sedikit sampel, development server, dan noise host. Payload daftar bertambah sekitar 0,3 kB karena breadcrumb JSON-LD. Tidak diklaim ada kenaikan performa HTTP; optimasi yang terverifikasi adalah penghilangan query detail duplikat melalui hitungan query feature test. Tidak ada dataset produksi/beban representatif untuk mengukur indeks, cache-hit, atau Core Web Vitals.

Migration `create_content_slug_redirects_table` berstatus **Pending** pada SQLite persisten; test menjalankan migration pada database SQLite terisolasi dan tidak mengubah database persisten. Terapkan migration saat deployment sebelum memakai fitur edit slug. Batas sitemap satu dokumen standar (50.000 URL) belum diuji pada skala tersebut; storage link produksi dan MySQL Laragon juga masih perlu diverifikasi.

Verifikasi tahap ini: seluruh suite Pest lulus **58 tes, 553 assertion**; Pint lulus pada file PHP yang diubah; `npm run build` lulus; pemeriksaan Problems pada file yang disentuh tidak menemukan error. Tidak ada seeder/data produksi, perubahan `.env`, atau migration pada database persisten.

## Tahap 8 Audit Akhir

### Keamanan dan Konfigurasi

- Menambahkan middleware header global yang juga mendukung response biasa maupun streamed: CSP same-origin dengan nonce per-request pada script Vite dan JSON-LD, `object-src 'none'`, `frame-ancestors 'none'`, `form-action 'self'`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, dan `Permissions-Policy`. HSTS hanya dikirim untuk environment production ketika Laravel mengenali request sebagai HTTPS. CSP saat ini masih mengizinkan inline style karena atribut style yang sudah ada; inline script tidak diizinkan.
- Regression test memeriksa keselarasan nonce seluruh script terhadap CSP, header, HSTS secure/insecure, dan atribut cookie session production `Secure`, `HttpOnly`, `SameSite=Lax`. Test streamed sitemap awalnya menemukan incompatibility middleware (`StreamedResponse` tidak memiliki `withHeaders()`); implementasi diperbaiki dengan menulis ke header bag Symfony dan test terkait kembali lulus.
- Verifikasi HTTP lokal: `GET /`, `/sitemap.xml`, dan `/robots.txt` masing-masing menghasilkan **200** dengan content type HTML/XML/plain text; POST login tanpa token CSRF menghasilkan **419**. Tidak ada percobaan login ber-kredensial atau perubahan data persisten.
- Suite yang ada mencakup autentikasi dan rotasi/invalidation session, error login generik serta rate limit, batas admin/IDOR, upload dan traversal, sanitasi XSS, draft/jadwal, aturan harga/kuantitas WhatsApp, dan invalidasi cache. Admin tetap berada di middleware `auth` dan gate `access-admin`; file publik hanya varian gambar WebP yang memang dimaksudkan untuk ditayangkan, bukan dokumen privat.
- Pada runtime lokal terdeteksi `APP_ENV=local`, `APP_DEBUG=true`, database SQLite, session driver database, lifetime 120 menit, cookie `HttpOnly`, SameSite Lax, dan Secure=false. Ini konfigurasi development, bukan bukti konfigurasi production. README menetapkan `APP_DEBUG=false`, HTTPS, secure cookie, trusted proxy yang tepat, database least privilege, dan penyimpanan secret terpisah sebagai persyaratan hosting.
- Audit dependency: `composer audit` tidak menemukan advisory; `npm audit` (termasuk dependency development) menemukan **0 vulnerabilities**. Ini hanya hasil feed audit saat pemeriksaan dan bukan jaminan bebas kerentanan.

### Hosting, Backup, dan Risiko Operasional

README starter Laravel diganti dengan instruksi setup, deployment melalui `public/`, environment production, build asset, migration/admin bootstrap, izin direktori, backup dan restore database/upload, maintenance, serta rekomendasi CDN/WAF/rate limit/DDoS. Backup/restore drill, konfigurasi provider WAF, host production, trusted proxy, dan MySQL belum dijalankan atau diverifikasi. `.htaccess` masih aturan front-controller Apache standar; aturan origin/web-server tambahan menjadi tanggung jawab konfigurasi host.

Migration `2026_10_13_082518_create_content_slug_redirects_table` tetap **Pending** pada SQLite persisten. Database tersebut tidak dimigrasikan pada tahap ini; lakukan backup, verifikasi migration pada staging database yang sesuai, lalu jalankan migration saat release sebelum menggunakan edit-slug/redirect. Tidak ada deploy, perubahan `.env`, atau klaim bahwa aplikasi kebal terhadap serangan.

### Visual, Performa, dan Verifikasi

Tidak ada CSS/desain yang diubah pada tahap audit. Catatan pemeriksaan visual desktop 1440x960 dan mobile 390x844 tetap tersedia dari tahap sebelumnya, tetapi perbandingan visual baru terhadap local build tidak dapat diselesaikan: browser terintegrasi tidak dapat menjangkau server lokal dan halaman referensi saat percobaan baru tidak ter-render. Karena itu tidak mengklaim visual desktop/mobile telah dibandingkan ulang atau bebas regresi. Performa produksi, Core Web Vitals, dataset besar, dan beban konkuren masih belum terukur; keterbatasan pengukuran fase 7 tetap berlaku.

Verifikasi akhir setelah perbaikan streamed response: **61 tes, 575 assertion lulus** menggunakan Pest dengan GD dan EXIF diaktifkan; test header/session terfokus **3 tes, 22 assertion lulus**. `npm run build` lulus dengan peringatan opsional `fontaine` yang belum terpasang; `composer audit` dan `npm audit` lulus; Pint dan pemeriksaan Problems pada file yang disentuh tidak melaporkan error. Cakupan ini bukan pengujian MySQL/production, audit penetrasi menyeluruh, atau jaminan keamanan.

## Tambahan 1 — Login dan Tambah Pengguna

Tahap tambahan ini dicatat terpisah dan tidak mengubah status tahap pembangunan lama.

- Login yang sudah ada dipertahankan: dua kolom desktop, identitas ringkas saat mobile, logo/ornamen SVG, label permanen, toggle kata sandi aksesibel, pemulihan email saja, pencegahan submit ganda, dan tautan kembali ke website. Tidak ada alur lupa kata sandi atau registrasi publik.
- Browser lokal memeriksa panel identitas dan form berdampingan pada desktop serta tersusun satu kolom pada lebar mobile; navigasi Tab mencapai toggle kata sandi dan tombol mengubah visibilitas beserta status aksesibelnya. Browser tidak melakukan percobaan kredensial nyata.
- Tambah pengguna kini tersedia dari dashboard dan navigasi admin. GET/POST hanya berada di grup `auth` + `can:access-admin`, dengan otorisasi ulang pada Form Request; tersedia field nama, email, kata sandi/konfirmasi, serta pilihan role berdasarkan kolom `is_admin` existing.
- Server menormalkan email ke huruf kecil, memvalidasi keunikan email, role boolean, konfirmasi dan panjang minimal kata sandi 12 karakter (sesuai command pembuatan admin). Kata sandi tidak menjadi input lama; data nonrahasia dipertahankan. Penyimpanan memakai hashed cast existing serta audit log dalam transaksi. Tidak ada migration atau kolom baru.
- Verifikasi: **21 tes, 137 assertion lulus** untuk pembuatan pengguna, otorisasi/penolakan tamu dan non-admin, validasi, hash/audit, login/logout, dan perlindungan role existing. `npm run build` lulus dengan peringatan opsional `fontaine`; Pint dan Problems tidak menemukan error. Tidak ada perubahan `.env` atau data database persisten.

## Tambahan 2 — Konsistensi Dashboard Admin

Tahap tambahan ini dicatat terpisah dari tahap pembangunan lama dan Tambahan 1.

- Layout admin memakai sidebar emerald responsif, navigasi mobile native yang dapat dibuka/ditutup, header akun/logout, dan breadcrumb; tidak lagi menampilkan header/footer publik di halaman admin. Semua tautan admin tetap di dalam middleware `auth` + gate `access-admin`; menu dan ringkasan pengguna juga mengikuti gate. Route tetap proteksi utama.
- Gaya admin reusable dibatasi ke shell admin: heading/card/statistik, toolbar/filter, tabel dan pagination, kartu konfirmasi hapus, field/form, tombol, badge, notifikasi sukses/error, empty/loading state, fokus keyboard, serta reduced-motion. Empat halaman daftar dan halaman forms/konfirmasi existing memakai tema yang sama. Tidak ada dependensi tambahan atau perubahan logika CRUD.
- Dashboard menghitung produk terbit melalui scope `published` existing, semua produk dengan stok nol, seluruh artikel (semua status), dan jumlah pengguna hanya ketika gate mengizinkan. Tidak ada statistik transaksi.
- Regression test memverifikasi nilai statistik dari data fixture, guest redirect, larangan non-admin, dan alur admin. Semua tes dashboard, autentikasi, tambah pengguna, katalog, dan konten: **40 tes, 396 assertion lulus** (GD/EXIF diaktifkan untuk tes upload). `npm run build` lulus dengan peringatan opsional bahwa `fontaine` tidak terpasang; Pint dan Problems bersih.
- Breakpoint CSS mobile dan reduced-motion telah ditambahkan, tetapi screenshot/browser responsif halaman dashboard terautentikasi belum diverifikasi. Tidak ada perubahan `.env`, migration, atau data persisten.

## Tambahan 3 — Pengaturan Beranda

Tahap tambahan ini dicatat terpisah dan tidak mengulang atau mengubah status tahap pembangunan lama.

- Pengaturan admin memakai tabel `site_settings` yang sudah ada untuk mengelola judul/deskripsi hero, CTA, serta visibilitas dan urutan enam section beranda. Banner tetap dikelola lewat CRUD/upload existing; urutan kategori, pilihan buku unggulan/bestseller admin, artikel terbaru, dan informasi layanan memakai sumber data/field existing. Bestseller tidak dinyatakan sebagai statistik penjualan.
- Beranda mempertahankan layout publik dan merender hanya section yang aktif sesuai urutan. Copy ditampilkan melalui escaping Blade; CTA dibatasi ke path internal atau URL HTTPS tervalidasi. Nilai section dibatasi ke daftar yang diketahui dan validasi server menolak konfigurasi/CTA tidak sah. Perubahan settings memicu invalidasi cache existing melalui event model `SiteSetting`; tidak ada migration, dependency, atau perubahan `.env`.
- Verifikasi: `HomepageManagementTest`, `PublicCatalogTest`, dan `AdminContentTest` lulus: **21 tes, 247 assertion**. Cakupan mencakup tampilan setting/urutan/visibilitas di publik, otorisasi admin, penolakan CTA tidak sah, dan invalidasi cache. `npm run build` lulus dengan peringatan opsional bahwa `fontaine` tidak terpasang; Pint dan Problems pada file terkait tidak menemukan error.
- Browser membuka homepage lokal dengan konten fixture yang telah tersedia. Pada viewport browser yang dikonfigurasi 390x844, layout tampil satu kolom dan `scrollWidth` sama dengan lebar layout viewport (tidak terdeteksi overflow horizontal). Browser melaporkan lebar CSS 468 px, sehingga pengukuran tepat pada 390 CSS px belum dapat dipastikan. Tidak ada data persisten yang diubah.

## Tambahan 4 — Pratinjau Publikasi

Tahap tambahan ini dicatat terpisah dan tidak mengulang tahap pembangunan lama.

- Produk menggunakan flag `is_published` existing; tombol Simpan Draft memaksa status tidak terbit dan menghapus waktu publikasi, sedangkan Publikasikan memvalidasi seluruh payload dan menetapkan status terbit. Artikel memakai enum/status existing; Simpan Draft memaksa status draft dan menghapus tanggal, Publikasikan memvalidasi lalu menetapkan status terbit. Pilihan jadwal artikel dan waktu terbit produk yang sudah ada tetap tersedia lewat penyimpanan biasa.
- Route pratinjau produk/artikel berada di grup `auth` + `can:access-admin`, merender view publik yang sama, menandai noindex melalui meta dan `X-Robots-Tag`, serta mengirim `Cache-Control: private, no-store`. Pratinjau produk menonaktifkan tindakan WhatsApp. Tautan preview hanya ada di halaman admin edit; produk/artikel draft tetap 404 pada URL publik dan dikecualikan dari sitemap sampai diterbitkan.
- Pengaturan beranda kini memiliki key draft terpisah dan nonpublik pada tabel `site_settings` existing. Simpan Draft hanya menulis konfigurasi hero/section draft; preview beranda memakai view publik dengan draft tersebut, dilindungi otorisasi admin dan header noindex/no-store. Publikasikan menulis settings publik dalam transaksi, menghapus draft, dan menginvalidasi cache `public-site-settings`. Tidak ada perubahan schema/migration.
- Produk dan artikel publik serta sitemap membaca status/data secara langsung tanpa cache halaman publik yang perlu dibersihkan; tes memverifikasi keduanya baru muncul setelah publikasi. Invalidasi cache settings beranda diuji terhadap cache yang sudah dipanaskan.
- Verifikasi final: suite penuh **77 tes, 765 assertion lulus** dengan GD/EXIF aktif; suite terfokus preview/katalog/konten/sitemap **40 tes, 520 assertion lulus**. `npm run build`, Pint, route listing admin, dan Problems untuk file terkait lulus. Build tetap menampilkan peringatan opsional `fontaine` belum terpasang. Preview diuji melalui feature test; browser visual/admin-auth tidak diuji pada tahap ini. Tidak ada perubahan `.env` atau data database persisten.

## Tambahan 5 — Perubahan Stok Manual

Tahap tambahan ini dicatat terpisah dan tidak mengulang tahap pembangunan lama.

- Penyesuaian stok admin dicatat sebagai riwayat append-only: arah, jumlah, alasan, catatan, snapshot stok sebelum/sesudah, produk, admin pelaksana, waktu, serta token UUID unik. Penjualan WhatsApp mengurangi stok hanya melalui pencatatan admin setelah konfirmasi; barang masuk menambah stok dan koreksi dapat memilih arah. Klik WhatsApp publik tetap tidak mengubah stok.
- Perubahan stok berjalan dalam transaksi, membaca produk dengan `lockForUpdate()`, membatasi update dengan nilai stok sebelumnya, memvalidasi agar stok tidak negatif atau melampaui batas integer, serta mencatat audit di transaksi yang sama. Token pengiriman mencegah submit ulang; konflik token unik yang terjadi serentak ditangani sebagai duplikasi untuk pemilik asal atau validasi untuk konteks berbeda. Field stok tidak lagi dapat menimpa stok lewat edit produk; stok awal tetap tersedia saat membuat produk.
- Halaman penyesuaian dan riwayat terpasang pada route admin yang ada di grup autentikasi/otorisasi. Riwayat memiliki filter alasan/arah/tanggal dan pagination; tidak ada route edit/hapus. Validasi Form Request membatasi jumlah positif integer serta kecocokan alasan dan arah. Pencegahan submit ganda di browser bersifat tambahan terhadap idempotensi server.
- Verifikasi: test stok/katalog/WhatsApp **29 tes, 24 lulus, 5 dilewati, 352 assertion**. Suite penuh Pest **81 tes, 69 lulus, 12 dilewati, 762 assertion** dengan GD dimuat untuk proses tes. Tes mencakup pengurangan berlebih, penambahan/pengurangan, token duplikat/kepemilikan, otorisasi, audit, stok stale, edit produk, filter/pagination, escaping catatan, dan route hapus yang tidak tersedia. Test WhatsApp existing memastikan stok tetap.
- `npm run build`, Pint, serta Problems pada file terkait lulus. Build memberi peringatan opsional bahwa `fontaine` tidak terpasang. Fitur dites memakai SQLite; tes stok stale berurutan bukan simulasi dua transaksi/database terpisah, sehingga interleaving konkurensi nyata dan perilaku row-lock MySQL belum diverifikasi. Migration baru tercakup melalui migrasi test, bukan diterapkan ke database lokal persisten. `.env` dan data database persisten tidak diubah.

## Tambahan 6 — Pengelolaan Gambar

Tahap tambahan ini dicatat terpisah dan tidak mengulang tahap pembangunan lama.

- Galeri produk memakai processor/validasi upload existing tanpa mengubah cara kompresi, WebP adaptif, varian, batas dimensi/memori, atau cara hapus file. Preview lokal memakai object URL (bukan base64), dilepas saat pilihan diganti, unggah sukses, atau halaman ditinggalkan. UI menampilkan ukuran sumber file dari `File.size` dan konfirmasi ukuran sumber dari unggahan serta ukuran/dimensi/jenis hasil dari metadata server/record varian tersimpan.
- Unggah interaktif dikirim satu file per request secara berurutan, dengan progres, status, dan error terpisah tiap file; batas jumlah yang tersisa dicek di UI dan tetap divalidasi server. Bila hasil request ambigu, UI meminta muat ulang/periksa galeri dan tidak mengulang file otomatis. File yang tidak berubah tidak dikirim ulang atau dikompres ulang. Upload form tetap memiliki fallback multipart existing bila JavaScript tidak tersedia.
- Pengurutan dapat dilakukan dengan input angka fallback dan tombol keyboard-native “Naik”, “Turun”, serta “Jadikan foto utama”; posisi pertama adalah foto utama sesuai alur penyimpanan existing. Foto utama tampil jelas. Hapus foto tetap melalui halaman konfirmasi existing dan processor hanya menghapus varian terkelola. Tata letak gallery/preview satu kolom di layar sempit dan berkembang pada breakpoint Tailwind; kontrol memiliki fokus yang terlihat dan motion menghormati preferensi reduced-motion.
- Verifikasi: suite penuh **83 tes, 83 lulus, 896 assertion** dengan GD dan EXIF diaktifkan; test admin katalog/gambar **17 tes, 17 lulus, 208 assertion**. Cakupan memastikan metadata sumber/hasil aktual pada respons per-file, unggah gagal, batas lima foto, pemilihan foto utama/urutan, konfirmasi dan penghapusan aman, serta batas kepemilikan. `npm run build`, Pint, dan Problems pada file terkait lulus; build memberi peringatan opsional bahwa `fontaine` tidak terpasang. Tidak ada migration atau dependency baru.
- Pengujian browser interaktif/ukuran viewport tidak dijalankan: server lokal pada tab bersama menolak koneksi. Responsivitas serta label/fokus diperiksa melalui struktur Blade/CSS dan feature test, bukan klaim uji browser/keyboard langsung. `.env`, data, dan alur publikasi gambar tidak diubah.

## Tambahan 7 — Keamanan akun admin

- 2FA TOTP bersifat opsional per akun dan menggunakan library OTPHP (`spomky-labs/otphp` `^11.5`, terkunci `11.5.0`), bukan algoritma buatan sendiri. Login dengan 2FA memverifikasi password lebih dahulu, kemudian meminta TOTP atau satu recovery code sebelum membuat sesi terautentikasi. Challenge dibatasi 5 percobaan per akun dan 20 per IP per menit; sidik waktu TOTP mencegah pemakaian ulang.
- Secret TOTP dan secret setup disimpan terenkripsi. Recovery code hanya ditampilkan sekali dalam respons `private, no-store`, disimpan sebagai hash, dan dikonsumsi saat digunakan. Aktivasi, penonaktifan, dan pembuatan ulang recovery code memerlukan password serta faktor yang valid dan dicatat ke audit. Tidak ada bypass admin untuk mereset faktor akun lain.
- Migration bersifat aditif: akun existing mendapat `is_active = true`; tidak ada data atau kolom existing yang dihapus. Middleware menolak akun nonaktif pada request berikutnya. Pengelolaan status dan larangan menonaktifkan admin aktif terakhir tetap ditegakkan server-side.
- Daftar dan pencabutan sesi tersedia hanya saat session driver `database` dan tabel sesi tersedia. Dengan driver lain, sesi tidak dapat dikelola/dicabut dari fitur ini; akun nonaktif tetap ditolak pada request berikutnya, tetapi pencabutan proaktif seluruh sesi tidak dijamin. Tidak ada fitur penghapusan akun baru.
- Prasyarat operasional: `APP_KEY` harus stabil untuk membaca secret terenkripsi; jam server harus tersinkron untuk TOTP; pengguna menyiapkan aplikasi autentikator dan menyimpan recovery code. Tidak ada QR setup atau jalur pemulihan admin darurat bila faktor dan recovery code hilang. Reset faktor akun oleh pihak lain bukan fitur yang disediakan.
- Verifikasi: suite penuh **95 tes, 95 lulus, 1.039 assertion** menggunakan SQLite in-memory; mencakup login TOTP/recovery, faktor invalid dan rate limit, sesi, akun nonaktif, otorisasi, serta admin aktif terakhir. Pint lulus, Problems tidak menemukan error pada file terkait, dan `npm run build` lulus (peringatan opsional `fontaine` tidak terpasang). Composer melaporkan tidak ada advisori keamanan untuk dependency yang dipasang.
- Migration tidak dijalankan terhadap database persisten. Pengujian tidak membuktikan interleaving konkurensi atau semantik row-lock pada MySQL/Laragon; hasil tersebut perlu diuji pada database lingkungan deploy sebelum rilis.

## Tambahan 8 — Backup dan pemulihan

- Menambahkan `backup:run`, dijadwalkan setiap hari pukul 02:00 pada timezone aplikasi (saat ini UTC) dengan `withoutOverlapping`, `onOneServer`, dan OS file lock selama eksekusi. Pendaftaran jadwal tidak mengaktifkan scheduler OS/hosting secara otomatis.
- Backup SQLite memakai `VACUUM INTO` untuk snapshot file-backed yang konsisten. MySQL/MariaDB menulis dump skema/tabel dan baris secara streaming dari PDO, tanpa menjalankan shell dump atau menaruh kredensial pada argumen proses. Driver database lain dan SQLite `:memory:` ditolak dengan error. File pada image disk terkonfigurasi disalin satu per satu ke folder `images/` dalam snapshot.
- Path `BACKUP_PATH` wajib absolut dan di luar root aplikasi; default-nya folder saudara `<project>-backups`. `BACKUP_IMAGE_DISK` mengikuti `PRODUCT_IMAGE_DISK` atau `public`; retensi default 30 hari diatur melalui `BACKUP_RETENTION_DAYS`. Root dibatasi mode `0700` pada Unix dan ACL NTFS akun proses pada Windows; pada inisialisasi pertama direktori harus khusus dan kosong. `.env`, kredensial DB, serta `APP_KEY` tidak disalin atau dicatat. Status, waktu, id snapshot, dan ukuran payload dicatat dalam `backup-history.jsonl` tanpa detail exception/rahasia.
- README mendokumentasikan Task Scheduler Windows/Laragon tiap menit, cron/scheduler hosting, pemisahan path/config lokal-hosting, retensi, keterbatasan konsistensi file saat upload paralel, serta restore SQLite/MySQL ke target kosong dan direktori gambar terpisah. Tidak ada endpoint/tombol restore. Backup ini tidak dienkripsi oleh aplikasi; gunakan volume dan replika off-site terenkripsi. Routines, triggers, views, grants, dan driver selain SQLite/MySQL/MariaDB tidak tercakup.
- Uji feature menjalankan perintah dengan SQLite file sementara dan disk foto fake; snapshot disalin ke file SQLite dan folder gambar pemulihan terpisah, lalu nilai row serta byte WebP valid diverifikasi. Cakupan juga menguji retensi dan skip saat lock sedang dipegang. **2 tes, 24 assertion lulus**. `schedule:list` menunjukkan `backup:run` pada `0 2 * * *`. Ini belum menguji MySQL, database hosting, foto produksi, atau konfigurasi Windows Task Scheduler/cron aktual. Tidak ada backup atas database persisten, restore produksi, migration, atau perubahan `.env`.

## Tambahan 9 — Pencarian, Statistik, dan Halaman Informasi

- Pencarian katalog tetap memakai GET, validasi allowlist, filter/pagination yang dipertahankan, dan fallback `LIKE` SQLite. MySQL/MariaDB menggunakan full-text untuk judul/penulis/penerbit serta pencarian kode/ISBN; migration menambahkan full-text secara bersyarat. Pencarian kosong menampilkan cara pulih dan tautan reset. Detail produk memberi label eksplisit harga satuan/grosir, minimum grosir, dan CTA WhatsApp yang jelas. Tidak ada keranjang/checkout.
- Metrik agregat menyimpan penghitung kunjungan produk dan permintaan pengalihan WhatsApp per produk/hari. Klik dicatat hanya setelah produk publik, jumlah, dan nomor WA valid; preview admin tidak dihitung dan stok tidak berubah. Deduplikasi per produk/sesi/jenis aktivitas menggunakan kunci HMAC sementara di cache; tabel tidak menyimpan IP, session ID, isi pesan, atau fingerprint. Angka pengalihan bukan bukti pesan terkirim, pesanan, atau penjualan. Retensi default 365 hari dan `metrics:prune` dijadwalkan harian dengan perlindungan overlap; `schedule:list` menampilkan `0 0 * * *`. Scheduler OS/hosting tidak otomatis aktif.
- Statistik agregat serta halaman administrasinya hanya tersedia lewat grup `auth` dan gate `access-admin`. Halaman Kebijakan Privasi dan Ketentuan Pemesanan dimulai dengan draft kosong dan petunjuk yang menyatakan konten belum diberikan; publikasi konten kosong ditolak. Admin dapat menyimpan draft, melihat preview privat (`noindex`, `no-store`), dan mempublikasikan teks biasa. Konten di-escape oleh Blade, cache settings terinvalidasi saat disimpan, dan halaman yang terbit saja muncul di navigasi/footer/sitemap. Tidak dibuat klaim retur, garansi, pembayaran, atau pengiriman.
- Saat Tambahan 9 dicatat, verifikasi SQLite in-memory menghasilkan suite penuh Pest **108 tes, 108 lulus, 1.161 assertion**; `npm run build`, Pint, Problems pada file terkait, dan `schedule:list` lulus. Build saat itu memberi peringatan opsional bahwa `fontaine` belum terpasang.
- Pada saat Tambahan 9 dicatat, MySQL/MariaDB, query plan indeks full-text, browser visual/keyboard/mobile, dan scheduler Windows/hosting aktual belum diuji. Dua tes gambar pada percobaan awal `artisan test` gagal karena proses tes anak tidak mewarisi ekstensi GD/EXIF; menjalankan Pest langsung dengan PHP 8.5 dan kedua ekstensi menghasilkan suite penuh lulus. Tidak ada migration pada database persisten, perubahan `.env`, atau data toko yang diubah.

## Penyempurnaan visual galeri produk

- Galeri detail produk memakai foto utama yang menonjol, latar ivory, thumbnail foto yang dapat dipilih dengan keyboard, indikator foto aktif, serta strip thumbnail yang dapat digulir di layar sempit. Foto tetap menggunakan varian WebP dan metadata yang sudah tersimpan; tidak ada gambar atau data produk yang diubah.
- Perubahan dibatasi pada presentasi galeri dan interaksi pemilihan foto. Informasi, tombol WhatsApp, rekomendasi, dan halaman lain tidak diubah. Transisi mematuhi `prefers-reduced-motion`.
- Verifikasi: `npm run build` lulus; `php artisan test --compact tests/Feature/PublicCatalogTest.php` menghasilkan **11 tes lulus, 128 assertion**; Problems tidak melaporkan error pada file terkait. Browser memuat foto produk existing dan tidak menemukan luapan horizontal pada viewport sempit. Kontrol beberapa foto diverifikasi pada render feature test; produk existing yang dicek hanya memiliki satu foto.

## Kesiapan Hosting — tindak lanjut

- Persyaratan PHP diselaraskan menjadi `^8.4.1`; PHP sistem 8.5.10 lulus `composer validate --strict` dan `composer check-platform-reqs --no-dev`. PHP Laragon 8.3.33 tidak kompatibel dengan dependency terkunci dan tidak boleh dipakai untuk CLI maupun web worker. `composer.lock` disegarkan tanpa perubahan versi paket.
- `npm run build` lulus setelah konfigurasi font Vite dibuat tidak bergantung pada optional `fontaine`; build produksi menghasilkan manifest dan aset tanpa error. GET lokal `/produk` menjawab HTTP 200.
- MySQL Laragon 8.4.3 diuji memakai schema baru yang terisolasi, lalu dihapus setelah pengujian. Seluruh migration berjalan, termasuk pencarian full-text dan migration baru yang memperlebar `audit_logs.subject_id` agar ID string dari pengaturan halaman dapat dicatat tanpa gagal. Empat file feature terkait menghasilkan **22 tes, 227 assertion lulus** pada MySQL. Tes katalog MySQL melakukan commit dan cleanup fixture karena indeks full-text InnoDB tidak menampilkan baris yang masih belum di-commit; pencarian setelah commit terverifikasi.
- Uji katalog SQLite standalone menghasilkan **11 tes, 123 assertion lulus**; suite SQLite penuh setelah perubahan menghasilkan **108 tes, 1.161 assertion lulus**. Pemeriksaan migration rollback pada schema MySQL terisolasi membuktikan ID numerik dapat dipulihkan dan rollback ditolak tanpa kehilangan data bila ID string sudah tercatat.
- `.env`, kunci aplikasi, database toko, dan upload yang ada tidak diubah. Tidak ada deploy. Provider/domain, runtime PHP web, credentials dan versi DB hosting, konfigurasi TLS/proxy, storage persisten/bersama, cache/session lintas instance, scheduler Windows/hosting, backup off-site, serta restore drill pada lingkungan target belum dikonfigurasi atau diverifikasi. Terapkan migration di target hanya setelah backup dan review deployment; verifikasi seluruh prasyarat pada host sebelum membuka traffic.
- Perbaikan upload lokal: request web sebelumnya gagal karena proses PHP `artisan serve` tidak mewarisi opsi `-d extension=gd -d extension=exif`; log menunjukkan GD/WebP tidak tersedia. PHP `C:\php\php.ini` kini mengaktifkan GD dan EXIF, menaikkan `memory_limit` ke 512M, menetapkan `upload_max_filesize=6M` dan `post_max_size=30M` (batch maksimal lima foto). `public/storage` semula belum ada; `artisan storage:link` membuat symlink ke `storage/app/public`. Server sudah direstart, detail produk merespons HTTP 200, tes upload galeri produk **25 tes/281 assertion** dan upload gambar artikel/banner **1 tes/10 assertion** lulus. Perubahan ini hanya berlaku untuk PHP lokal di `C:\php`, bukan Laragon PHP 8.3 atau runtime hosting.
