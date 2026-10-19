<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Banner;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PublicSiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function adminArticlePayload(array $overrides = []): array
{
    return array_replace([
        'title' => 'Panduan Membaca Kitab',
        'slug' => 'panduan-membaca-kitab',
        'summary' => 'Ringkasan artikel yang informatif.',
        'body' => '<h2>Pengantar</h2><p>Isi <strong>artikel</strong>.</p>',
        'status' => 'draft',
        'published_at' => '',
        'meta_title' => 'Panduan membaca kitab',
        'meta_description' => 'Panduan untuk pembaca.',
    ], $overrides);
}

function adminBannerPayload(array $overrides = []): array
{
    return array_replace([
        'eyebrow' => 'Kabar',
        'title' => 'Informasi toko',
        'description' => 'Informasi banner Alfikar.',
        'image_alt' => 'Banner informasi',
        'link_url' => '/artikel',
        'link_label' => 'Baca artikel',
        'starts_at' => '',
        'ends_at' => '',
        'sort_order' => 0,
        'is_active' => '1',
    ], $overrides);
}

it('protects article, banner, and site setting administration', function () {
    $article = Article::factory()->create();
    $banner = Banner::factory()->create();

    $this->get(route('admin.articles.index'))->assertRedirect(route('login'));
    $this->get(route('admin.banners.index'))->assertRedirect(route('login'));
    $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.articles.edit', $article))->assertForbidden();
    $this->put(route('admin.settings.update'), ['profile_name' => 'Tidak boleh'])->assertForbidden();
    $this->delete(route('admin.banners.destroy', $banner))->assertForbidden();

    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get(route('admin.articles.create'))->assertOk();
    $this->get(route('admin.banners.create'))->assertOk();
    $this->get(route('admin.settings.edit'))->assertOk();
});

it('creates and updates sanitized articles with permanent redirects from previous slugs', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.articles.store'), adminArticlePayload([
        'body' => '<h2 onclick="alert(1)">Pengantar</h2><script>alert(1)</script><p><a href="javascript:alert(1)">Aman</a></p><iframe src="https://evil.test"></iframe>',
    ]))->assertRedirect();

    $article = Article::query()->where('slug', 'panduan-membaca-kitab')->firstOrFail();
    expect($article->status)->toBe(ArticleStatus::Draft)
        ->and($article->published_at)->toBeNull()
        ->and($article->body)->toContain('<h2>Pengantar</h2>')
        ->and($article->body)->not->toContain('<script>')
        ->and($article->body)->not->toContain('onclick')
        ->and($article->body)->not->toContain('javascript:')
        ->and($article->body)->not->toContain('<iframe');

    $this->get(route('articles.show', $article->slug))->assertNotFound();

    $this->put(route('admin.articles.update', $article), adminArticlePayload([
        'title' => 'Panduan membaca yang diperbarui',
        'slug' => 'panduan-membaca-yang-diperbarui',
        'status' => 'published',
        'published_at' => '',
        'body' => '<p>Konten baru <strong>aman</strong>.</p>',
    ]))->assertRedirect(route('admin.articles.edit', $article));

    expect($article->fresh()->slug)->toBe('panduan-membaca-yang-diperbarui')
        ->and($article->fresh()->status)->toBe(ArticleStatus::Published)
        ->and($article->fresh()->published_at)->not->toBeNull();
    $this->assertDatabaseHas('article_slug_redirects', [
        'article_id' => $article->id,
        'slug' => 'panduan-membaca-kitab',
    ]);

    $this->get(route('articles.show', 'panduan-membaca-yang-diperbarui'))
        ->assertOk()
        ->assertSee('<strong>aman</strong>', false)
        ->assertDontSee('<script>', false);
    $this->get(route('articles.show', 'panduan-membaca-kitab'))
        ->assertMovedPermanently()
        ->assertRedirect(route('articles.show', 'panduan-membaca-yang-diperbarui'));
    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), adminArticlePayload([
            'slug' => 'panduan-membaca-kitab',
        ]))
        ->assertRedirect(route('admin.articles.create'))
        ->assertSessionHasErrors('slug');

    $legacyArticle = Article::factory()->create([
        'title' => 'Artikel lama',
        'slug' => 'artikel-lama',
        'status' => ArticleStatus::Published,
        'published_at' => now()->subMinute(),
        'body' => '<p onclick="alert(1)">Teks aman</p><script>alert(1)</script>',
    ]);
    $this->get(route('articles.show', $legacyArticle->slug))
        ->assertOk()
        ->assertSee('Teks aman')
        ->assertDontSee('onclick', false)
        ->assertDontSee('<script>', false);

    $this->put(route('admin.articles.update', $article), adminArticlePayload([
        'title' => 'Panduan membaca yang diperbarui',
        'slug' => 'panduan-membaca-yang-diperbarui',
        'status' => 'draft',
        'published_at' => '',
    ]))->assertRedirect(route('admin.articles.edit', $article));
    $this->get(route('articles.show', 'panduan-membaca-kitab'))->assertNotFound();
    $this->get(route('articles.show', 'panduan-membaca-yang-diperbarui'))->assertNotFound();

    $this->get(route('admin.articles.confirm-delete', $article))->assertOk();
    $this->delete(route('admin.articles.destroy', $article))->assertRedirect(route('admin.articles.index'));
    $this->assertModelMissing($article);
    $this->assertDatabaseMissing('article_slug_redirects', ['article_id' => $article->id]);
});

it('keeps scheduled articles private until their publication time', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);
    $publicationTime = now()->addMinutes(5);

    $this->post(route('admin.articles.store'), adminArticlePayload([
        'slug' => 'artikel-terjadwal',
        'status' => 'scheduled',
        'published_at' => $publicationTime->format('Y-m-d\TH:i'),
    ]))->assertRedirect();

    $article = Article::query()->where('slug', 'artikel-terjadwal')->firstOrFail();
    $this->get(route('articles.index'))->assertDontSee('Panduan Membaca Kitab');
    $this->get(route('articles.show', $article->slug))->assertNotFound();

    $this->travel(6)->minutes();
    $this->get(route('articles.index'))->assertSee('Panduan Membaca Kitab');
    $this->get(route('articles.show', $article->slug))->assertOk();
});

it('rejects invalid publication schedules and unsafe site URLs without writing them', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), adminArticlePayload([
            'status' => 'scheduled',
            'published_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ]))
        ->assertRedirect(route('admin.articles.create'))
        ->assertSessionHasErrors('published_at');
    $this->assertDatabaseCount('articles', 0);

    $this->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), [
            'whatsapp_number' => '628123',
            'contact_map_url' => 'https://evil.example/maps',
            'social_instagram' => 'javascript:alert(1)',
        ])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasErrors(['whatsapp_number', 'contact_map_url', 'social_instagram']);

    $this->assertDatabaseCount('site_settings', 0);
});

it('invalidates cached public settings after saving profile, SEO, WhatsApp, and map values', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    SiteSetting::factory()->create([
        'setting_key' => 'profile_name',
        'setting_value' => 'Nama Sebelum',
        'is_public' => true,
    ]);
    expect(app(PublicSiteSettings::class)->values(['profile_name'])['profile_name'])->toBe('Nama Sebelum');

    $this->actingAs($admin)->put(route('admin.settings.update'), [
        'profile_name' => 'Alfikar Baru',
        'profile_description' => 'Profil yang diperbarui.',
        'contact_address' => 'Alamat Alfikar.',
        'contact_email' => 'halo@example.test',
        'contact_phone' => '021123456',
        'whatsapp_number' => '+6281234567890',
        'contact_map_url' => 'https://www.google.com/maps/place/Alfikar',
        'social_instagram' => 'https://instagram.com/alfikar',
        'seo_title' => 'SEO Judul Alfikar',
        'seo_description' => 'Profil dan katalog buku Alfikar.',
        'service_delivery' => 'Layanan pengiriman.',
    ])->assertRedirect(route('admin.settings.edit'));

    expect(app(PublicSiteSettings::class)->values(['profile_name'])['profile_name'])->toBe('Alfikar Baru');
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Alfikar Baru')
        ->assertSee('<title>SEO Judul Alfikar | Alfikar Baru</title>', false)
        ->assertSee('content="Profil dan katalog buku Alfikar."', false);
    $this->get(route('pages.contact'))->assertSee('Lihat lokasi di Google Maps');
    $this->assertDatabaseHas('site_settings', [
        'setting_key' => 'whatsapp_number',
        'setting_value' => '+6281234567890',
        'is_public' => true,
    ]);
});

it('publishes the editable business name and optimized logo in the public header', function () {
    Storage::fake(config('image-processing.disk', 'public'));
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->put(route('admin.settings.update'), [
        'profile_name' => 'Alfikar Pustaka',
        'logo' => UploadedFile::fake()->image('logo.png', 160, 80),
    ])->assertRedirect(route('admin.settings.edit'));

    $logoPath = SiteSetting::query()->where('setting_key', 'site_logo_path')->value('setting_value');

    expect($logoPath)->toBeString()->toEndWith('.webp');
    Storage::disk(config('image-processing.disk', 'public'))->assertExists($logoPath);
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Alfikar Pustaka')
        ->assertSee('alt="Logo Alfikar Pustaka"', false)
        ->assertSee($logoPath);
    $this->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Nama usaha / teks di TOP Bar')
        ->assertSee('Logo usaha / website');
});

it('replaces or removes the current public logo and deletes the superseded file', function () {
    Storage::fake(config('image-processing.disk', 'public'));
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->put(route('admin.settings.update'), [
        'logo' => UploadedFile::fake()->image('first-logo.png', 100, 80),
    ])->assertRedirect(route('admin.settings.edit'));
    $firstLogoPath = SiteSetting::query()->where('setting_key', 'site_logo_path')->value('setting_value');

    $this->put(route('admin.settings.update'), [
        'logo' => UploadedFile::fake()->image('second-logo.png', 120, 80),
    ])->assertRedirect(route('admin.settings.edit'));
    $secondLogoPath = SiteSetting::query()->where('setting_key', 'site_logo_path')->value('setting_value');

    expect($secondLogoPath)->not->toBe($firstLogoPath);
    Storage::disk(config('image-processing.disk', 'public'))->assertMissing($firstLogoPath);
    Storage::disk(config('image-processing.disk', 'public'))->assertExists($secondLogoPath);

    $this->put(route('admin.settings.update'), ['remove_logo' => '1'])
        ->assertRedirect(route('admin.settings.edit'));

    expect(SiteSetting::query()->where('setting_key', 'site_logo_path')->value('setting_value'))->toBeNull();
    Storage::disk(config('image-processing.disk', 'public'))->assertMissing($secondLogoPath);
    $this->get(route('home'))->assertOk()->assertDontSee('class="site-brand__logo"', false);
});

it('rejects unsupported site logo uploads without changing public settings', function () {
    Storage::fake(config('image-processing.disk', 'public'));
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), [
            'profile_name' => 'Nama Tidak Tersimpan',
            'logo' => UploadedFile::fake()->create('logo.svg', 20, 'image/svg+xml'),
        ])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasErrors('logo');

    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'site_logo_path']);
    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'profile_name']);
});

it('manages active banner content and validates banner destinations', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.banners.store'), adminBannerPayload())
        ->assertRedirect();

    $banner = Banner::query()->firstOrFail();
    expect($banner->is_active)->toBeTrue();
    $this->get(route('home'))->assertSee('Informasi toko')->assertSee('/artikel');
    $this->put(route('admin.banners.update', $banner), adminBannerPayload([
        'title' => 'Banner diperbarui',
        'is_active' => '0',
    ]))->assertRedirect(route('admin.banners.edit', $banner));
    expect($banner->fresh()->title)->toBe('Banner diperbarui')
        ->and($banner->fresh()->is_active)->toBeFalse();

    $this->from(route('admin.banners.create'))
        ->post(route('admin.banners.store'), adminBannerPayload([
            'title' => 'Banner URL tidak aman',
            'link_url' => 'javascript:alert(1)',
        ]))
        ->assertRedirect(route('admin.banners.create'))
        ->assertSessionHasErrors('link_url');

    $this->assertDatabaseCount('banners', 1);
    $this->get(route('admin.banners.confirm-delete', $banner))->assertOk();
    $this->delete(route('admin.banners.destroy', $banner))->assertRedirect(route('admin.banners.index'));
    $this->assertModelMissing($banner);
});

it('stores and removes article and banner images through the shared image processor', function () {
    Storage::fake('public');
    config(['image-processing.disk' => 'public']);
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.articles.store'), adminArticlePayload([
        'image' => UploadedFile::fake()->image('article.png', 80, 120),
        'image_alt' => 'Sampul artikel',
    ]))->assertRedirect();

    $article = Article::query()->firstOrFail();
    expect($article->image_path)->toStartWith('articles/'.$article->getKey().'/');
    expect(Storage::disk('public')->exists($article->image_path))->toBeTrue();
    $articleImagePath = $article->image_path;

    $this->put(route('admin.articles.update', $article), adminArticlePayload([
        'image_alt' => 'Sampul yang diperbarui',
    ]))->assertRedirect();
    expect($article->fresh()->image_alt)->toBe('Sampul yang diperbarui');

    $this->put(route('admin.articles.update', $article), adminArticlePayload([
        'remove_image' => '1',
    ]))->assertRedirect();

    expect(Storage::disk('public')->exists($articleImagePath))->toBeFalse();

    $this->post(route('admin.banners.store'), adminBannerPayload([
        'image' => UploadedFile::fake()->image('banner.png', 80, 120),
    ]))->assertRedirect();

    $banner = Banner::query()->firstOrFail();
    expect($banner->image_path)->toStartWith('banners/'.$banner->getKey().'/');
    expect(Storage::disk('public')->exists($banner->image_path))->toBeTrue();
});

it('previews draft articles privately and only exposes them publicly after publication', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $article = Article::factory()->for($admin, 'author')->create([
        'title' => 'Artikel Draft Pratinjau',
        'slug' => 'artikel-draft-pratinjau',
        'status' => ArticleStatus::Draft,
        'published_at' => null,
        'body' => '<h2>Isi pratinjau</h2><script>alert(1)</script>',
    ]);

    $this->get(route('admin.articles.preview', $article))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.articles.preview', $article))->assertForbidden();

    $preview = $this->actingAs($admin)
        ->get(route('admin.articles.preview', $article))
        ->assertOk()
        ->assertSee('Artikel Draft Pratinjau')
        ->assertSee('Pratinjau admin')
        ->assertSee('noindex,nofollow')
        ->assertSee('<h2>Isi pratinjau</h2>', false)
        ->assertDontSee('<script>', false);
    $preview->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->get(route('admin.articles.edit', $article))
        ->assertOk()
        ->assertSee('Simpan Draft')
        ->assertSee('Pratinjau')
        ->assertSee('Publikasikan');
    $this->get(route('articles.show', $article->slug))->assertNotFound();
    expect($this->get(route('sitemap'))->streamedContent())->not->toContain('artikel-draft-pratinjau');
    expect($article->fresh()->status)->toBe(ArticleStatus::Draft)
        ->and($article->fresh()->published_at)->toBeNull();

    $this->from(route('admin.articles.edit', $article))
        ->put(route('admin.articles.update', $article), adminArticlePayload([
            'title' => 'Artikel Draft Pratinjau',
            'slug' => 'artikel-draft-pratinjau',
            'status' => 'draft',
            'publication_action' => 'publish',
            'published_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]))
        ->assertRedirect(route('admin.articles.edit', $article))
        ->assertSessionHasErrors('published_at');
    expect($article->fresh()->status)->toBe(ArticleStatus::Draft);

    $this->put(route('admin.articles.update', $article), adminArticlePayload([
        'title' => 'Artikel Draft Pratinjau',
        'slug' => 'artikel-draft-pratinjau',
        'status' => 'draft',
        'publication_action' => 'publish',
        'published_at' => '',
        'body' => '<p>Konten yang dipublikasikan.</p>',
    ]))->assertRedirect(route('admin.articles.edit', $article));

    expect($article->fresh()->status)->toBe(ArticleStatus::Published)
        ->and($article->fresh()->published_at)->not->toBeNull();
    $this->get(route('articles.show', $article->slug))->assertOk()->assertSee('Konten yang dipublikasikan.');
    expect($this->get(route('sitemap'))->streamedContent())->toContain('artikel-draft-pratinjau');
});
