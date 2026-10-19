@extends('layouts.admin')

@section('title', 'Pengaturan situs')

@section('content')
    <p class="admin-eyebrow">Konfigurasi publik</p>
    <h1 class="font-display text-3xl text-brand-dark">Pengaturan situs</h1>
    @if ($hasHomepageDraft)
        <p class="mt-3 text-sm text-ink/70">
            Draft beranda tersimpan.
            <a class="text-brand underline underline-offset-4" href="{{ route('admin.settings.homepage-preview') }}">Pratinjau</a>
        </p>
    @endif
    @php
        $defaultHomeSections = [
            'hero' => 'Hero dan banner',
            'categories' => 'Kategori',
            'services' => 'Informasi layanan',
            'bestsellers' => 'Bestseller pilihan admin',
            'products' => 'Katalog buku',
            'articles' => 'Artikel terbaru',
        ];
        $homeSections = json_decode($settings['home_sections'] ?? '', true);
        $homeSections = is_array($homeSections) ? $homeSections : [];
    @endphp
    <form class="admin-form-panel mt-7 max-w-4xl" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="grid gap-5 md:grid-cols-2">
            <section class="admin-card grid gap-4 p-5 md:col-span-2" aria-labelledby="home-hero-settings-title">
                <div>
                    <h2 id="home-hero-settings-title" class="font-display text-xl text-brand-dark">Hero beranda</h2>
                    <p class="mt-1 text-sm text-ink/70">Banner bergambar diatur di menu Banner. Gambar buku unggulan mengikuti pilihan produk unggulan.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="home_hero_title">Judul hero</label>
                    <input class="mt-1 w-full border border-rule px-3 py-2" id="home_hero_title" name="home_hero_title" value="{{ old('home_hero_title', $settings['home_hero_title'] ?? '') }}" maxlength="255" aria-describedby="home-hero-title-hint">
                    <p class="mt-1 text-xs text-ink/60" id="home-hero-title-hint">Kosongkan untuk memakai judul buku unggulan atau teks bawaan.</p>
                    @error('home_hero_title')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="home_hero_description">Deskripsi hero</label>
                    <textarea class="mt-1 w-full border border-rule px-3 py-2" id="home_hero_description" name="home_hero_description" rows="3" maxlength="2000">{{ old('home_hero_description', $settings['home_hero_description'] ?? '') }}</textarea>
                    @error('home_hero_description')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium" for="home_hero_cta_label">Teks tombol</label>
                        <input class="mt-1 w-full border border-rule px-3 py-2" id="home_hero_cta_label" name="home_hero_cta_label" value="{{ old('home_hero_cta_label', $settings['home_hero_cta_label'] ?? '') }}" maxlength="100">
                        @error('home_hero_cta_label')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="home_hero_cta_url">Tujuan tombol</label>
                        <input class="mt-1 w-full border border-rule px-3 py-2" id="home_hero_cta_url" name="home_hero_cta_url" type="text" value="{{ old('home_hero_cta_url', $settings['home_hero_cta_url'] ?? '') }}" maxlength="2048" placeholder="/produk">
                        <p class="mt-1 text-xs text-ink/60">Gunakan path lokal seperti /produk atau URL HTTPS.</p>
                        @error('home_hero_cta_url')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="admin-card grid gap-4 p-5 md:col-span-2" aria-labelledby="home-sections-title">
                <div>
                    <h2 id="home-sections-title" class="font-display text-xl text-brand-dark">Bagian beranda</h2>
                    <p class="mt-1 text-sm text-ink/70">Pilih visibilitas dan posisi tiap bagian. Setiap posisi harus unik.</p>
                </div>
                <div class="grid gap-3">
                    @foreach ($defaultHomeSections as $key => $label)
                        @php($sectionDefaults = $homeSections[$key] ?? ['enabled' => true, 'order' => $loop->iteration])
                        <fieldset class="grid gap-3 border border-rule p-4 sm:grid-cols-[minmax(0,1fr)_minmax(140px,0.7fr)_minmax(100px,0.5fr)] sm:items-end">
                            <legend class="px-1 text-sm font-semibold text-brand-dark">{{ $label }}</legend>
                            <div>
                                <label class="block text-sm font-medium" for="home_sections_{{ $key }}_enabled">Tampilan</label>
                                <select class="mt-1 w-full border border-rule px-3 py-2" id="home_sections_{{ $key }}_enabled" name="home_sections[{{ $key }}][enabled]" required>
                                    <option value="1" @selected((string) old("home_sections.{$key}.enabled", $sectionDefaults['enabled'] ? '1' : '0') === '1')>Aktif</option>
                                    <option value="0" @selected((string) old("home_sections.{$key}.enabled", $sectionDefaults['enabled'] ? '1' : '0') === '0')>Nonaktif</option>
                                </select>
                                @error("home_sections.{$key}.enabled")<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium" for="home_sections_{{ $key }}_order">Urutan</label>
                                <input class="mt-1 w-full border border-rule px-3 py-2" id="home_sections_{{ $key }}_order" name="home_sections[{{ $key }}][order]" type="number" min="1" max="6" value="{{ old("home_sections.{$key}.order", $sectionDefaults['order']) }}" required>
                                @error("home_sections.{$key}.order")<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                @error('home_sections')<p class="text-sm text-red-800">{{ $message }}</p>@enderror
            </section>

            <section class="admin-card grid gap-4 p-5 md:col-span-2" aria-labelledby="profile-settings-title">
                <h2 id="profile-settings-title" class="font-display text-xl text-brand-dark">Profil</h2>
                <div>
                    <label class="block text-sm font-medium" for="profile_name">Nama usaha / teks di TOP Bar</label>
                    <input class="mt-1 w-full border border-rule px-3 py-2" id="profile_name" name="profile_name" value="{{ old('profile_name', $settings['profile_name'] ?? 'Toko Buku Alfikar') }}" maxlength="255">
                    <p class="mt-1 text-xs text-ink/60">Nama ini ditampilkan di header, footer, dan informasi situs.</p>
                    @error('profile_name')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-3">
                    <label class="block text-sm font-medium" for="logo">Logo usaha / website</label>
                    @if ($siteLogoUrl)
                        <img class="h-16 w-fit max-w-48 object-contain" src="{{ $siteLogoUrl }}" alt="Logo saat ini">
                        <label class="flex items-center gap-2 text-sm">
                            <input name="remove_logo" type="checkbox" value="1" @checked(old('remove_logo'))>
                            Hapus logo saat ini
                        </label>
                    @endif
                    <input class="w-full border border-rule px-3 py-2" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                    <p class="text-xs text-ink/60">Format JPEG, PNG, atau WebP; maksimal 5 MB. Logo akan dioptimalkan dan ditampilkan di bagian atas situs.</p>
                    @error('logo')<p class="text-sm text-red-800">{{ $message }}</p>@enderror
                </div>
                <div><label class="block text-sm font-medium" for="profile_description">Tentang usaha</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="profile_description" name="profile_description" rows="5" maxlength="10000">{{ old('profile_description', $settings['profile_description'] ?? '') }}</textarea></div>
                <div><label class="block text-sm font-medium" for="profile_address">Alamat profil</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="profile_address" name="profile_address" rows="2" maxlength="2000">{{ old('profile_address', $settings['profile_address'] ?? '') }}</textarea></div>
            </section>

            <section class="admin-card grid gap-4 p-5 md:col-span-2" aria-labelledby="contact-settings-title">
                <h2 id="contact-settings-title" class="font-display text-xl text-brand-dark">Kontak, WhatsApp, dan lokasi</h2>
                <div><label class="block text-sm font-medium" for="contact_address">Alamat kontak</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="contact_address" name="contact_address" rows="2" maxlength="2000">{{ old('contact_address', $settings['contact_address'] ?? '') }}</textarea></div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div><label class="block text-sm font-medium" for="contact_email">Email</label><input class="mt-1 w-full border border-rule px-3 py-2" id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" maxlength="255"></div>
                    <div><label class="block text-sm font-medium" for="contact_phone">Telepon</label><input class="mt-1 w-full border border-rule px-3 py-2" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}" maxlength="50"></div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div><label class="block text-sm font-medium" for="whatsapp_number">WhatsApp (format +E.164)</label><input class="mt-1 w-full border border-rule px-3 py-2" id="whatsapp_number" name="whatsapp_number" type="tel" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}" placeholder="+6281234567890"></div>
                    <div><label class="block text-sm font-medium" for="contact_map_url">URL Google Maps (tanpa iframe)</label><input class="mt-1 w-full border border-rule px-3 py-2" id="contact_map_url" name="contact_map_url" type="url" value="{{ old('contact_map_url', $settings['contact_map_url'] ?? '') }}" maxlength="2048"><p class="mt-1 text-xs text-ink/60">Hanya disimpan sebagai tautan https://maps.google.com, google.com/maps, atau maps.app.goo.gl; tidak mengambil URL dan tidak menanam iframe.</p></div>
                </div>
            </section>

            <section class="admin-card grid gap-4 p-5 md:col-span-2" aria-labelledby="social-settings-title">
                <h2 id="social-settings-title" class="font-display text-xl text-brand-dark">Media sosial (URL HTTPS)</h2>
                <div><label class="block text-sm font-medium" for="social_instagram">Instagram</label><input class="mt-1 w-full border border-rule px-3 py-2" id="social_instagram" name="social_instagram" type="url" value="{{ old('social_instagram', $settings['social_instagram'] ?? '') }}" maxlength="2048"></div>
                <div><label class="block text-sm font-medium" for="social_facebook">Facebook</label><input class="mt-1 w-full border border-rule px-3 py-2" id="social_facebook" name="social_facebook" type="url" value="{{ old('social_facebook', $settings['social_facebook'] ?? '') }}" maxlength="2048"></div>
                <div><label class="block text-sm font-medium" for="social_youtube">YouTube</label><input class="mt-1 w-full border border-rule px-3 py-2" id="social_youtube" name="social_youtube" type="url" value="{{ old('social_youtube', $settings['social_youtube'] ?? '') }}" maxlength="2048"></div>
            </section>

            <section class="admin-card grid gap-4 p-5 md:col-span-2" aria-labelledby="seo-settings-title">
                <h2 id="seo-settings-title" class="font-display text-xl text-brand-dark">SEO situs</h2>
                <div><label class="block text-sm font-medium" for="seo_title">Judul default situs</label><input class="mt-1 w-full border border-rule px-3 py-2" id="seo_title" name="seo_title" value="{{ old('seo_title', $settings['seo_title'] ?? '') }}" maxlength="255"></div>
                <div><label class="block text-sm font-medium" for="seo_description">Deskripsi default situs</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="seo_description" name="seo_description" rows="3" maxlength="320">{{ old('seo_description', $settings['seo_description'] ?? '') }}</textarea></div>
            </section>

            <section class="admin-card grid gap-4 p-5 md:col-span-2" aria-labelledby="services-settings-title">
                <h2 id="services-settings-title" class="font-display text-xl text-brand-dark">Informasi layanan beranda</h2>
                <div><label class="block text-sm font-medium" for="service_delivery">Pengiriman</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="service_delivery" name="service_delivery" rows="2" maxlength="2000">{{ old('service_delivery', $settings['service_delivery'] ?? '') }}</textarea></div>
                <div><label class="block text-sm font-medium" for="service_payment">Pembayaran</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="service_payment" name="service_payment" rows="2" maxlength="2000">{{ old('service_payment', $settings['service_payment'] ?? '') }}</textarea></div>
                <div><label class="block text-sm font-medium" for="service_returns">Pengembalian</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="service_returns" name="service_returns" rows="2" maxlength="2000">{{ old('service_returns', $settings['service_returns'] ?? '') }}</textarea></div>
                <div><label class="block text-sm font-medium" for="service_hours">Jam layanan</label><textarea class="mt-1 w-full border border-rule px-3 py-2" id="service_hours" name="service_hours" rows="2" maxlength="2000">{{ old('service_hours', $settings['service_hours'] ?? '') }}</textarea></div>
            </section>
        </div>
        <p class="mt-6 text-sm text-ink/70">Simpan Draft hanya menyimpan perubahan beranda. Publikasikan menerapkan pengaturan yang ditampilkan di formulir.</p>
        <div class="mt-3 flex flex-wrap gap-3">
            <button class="border border-brand px-5 py-2 text-sm text-brand-dark hover:bg-ivory" type="submit" formaction="{{ route('admin.settings.homepage-draft') }}">Simpan Draft</button>
            @if ($hasHomepageDraft)
                <a class="border border-rule px-5 py-2 text-sm text-brand-dark hover:bg-ivory" href="{{ route('admin.settings.homepage-preview') }}">Pratinjau</a>
            @endif
            <button class="bg-brand px-5 py-2 text-sm text-white hover:bg-brand-dark" type="submit">Publikasikan</button>
        </div>
    </form>
@endsection
