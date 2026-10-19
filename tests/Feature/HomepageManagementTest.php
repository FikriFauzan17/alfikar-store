<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PublicSiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function homepageSettingsPayload(array $overrides = []): array
{
    $sections = [
        'hero' => ['enabled' => '1', 'order' => '1'],
        'categories' => ['enabled' => '1', 'order' => '2'],
        'services' => ['enabled' => '1', 'order' => '3'],
        'bestsellers' => ['enabled' => '1', 'order' => '4'],
        'products' => ['enabled' => '1', 'order' => '5'],
        'articles' => ['enabled' => '1', 'order' => '6'],
    ];

    return array_replace([
        'home_hero_title' => 'Buku pilihan untuk semua',
        'home_hero_description' => 'Temukan bacaan bermakna.',
        'home_hero_cta_label' => 'Jelajahi koleksi',
        'home_hero_cta_url' => '/produk',
        'home_sections' => $sections,
    ], $overrides);
}

it('renders saved homepage copy and section order after the public settings cache is primed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create(['is_published' => true]);
    Product::factory()->create([
        'category_id' => $category->id,
        'title' => 'Buku unggulan Alfikar',
        'is_published' => true,
        'is_featured' => true,
    ]);
    Banner::factory()->create([
        'title' => 'Banner aktif Alfikar',
        'is_active' => true,
    ]);
    Article::factory()->create([
        'title' => 'Artikel pilihan Alfikar',
        'slug' => 'artikel-pilihan-alfikar',
        'status' => ArticleStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    app(PublicSiteSettings::class)->values(['home_hero_title']);

    $sections = homepageSettingsPayload()['home_sections'];
    $sections['articles']['order'] = '2';
    $sections['categories']['order'] = '6';

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), homepageSettingsPayload([
            'home_hero_title' => '<script>alert(1)</script>Inspirasi baru',
            'home_hero_description' => 'Deskripsi yang dapat dikelola admin.',
            'home_hero_cta_label' => 'Temukan buku',
            'home_hero_cta_url' => '/produk?sort=title',
            'home_sections' => $sections,
            'service_delivery' => 'Pengiriman dikelola dari admin.',
        ]))
        ->assertRedirect(route('admin.settings.edit'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Inspirasi baru')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Deskripsi yang dapat dikelola admin.')
        ->assertSee('Temukan buku')
        ->assertSee('href="/produk?sort=title"', false)
        ->assertSee('Banner aktif Alfikar')
        ->assertSee('Buku unggulan Alfikar')
        ->assertSee('Artikel pilihan Alfikar')
        ->assertSee('Pengiriman dikelola dari admin.');

    $body = $this->get(route('home'))->getContent();
    expect(strpos($body, 'latest-articles-title'))
        ->toBeLessThan(strpos($body, 'categories-title'));

    $this->assertDatabaseHas('site_settings', [
        'setting_key' => 'home_sections',
        'is_public' => true,
    ]);
});

it('hides disabled homepage sections without exposing unpublished content', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $sections = homepageSettingsPayload()['home_sections'];
    $sections['services']['enabled'] = '0';
    $sections['bestsellers']['enabled'] = '0';
    $sections['hero']['enabled'] = '0';
    $sections['products']['enabled'] = '0';
    $category = Category::factory()->create(['is_published' => true]);
    Product::factory()->create([
        'category_id' => $category->id,
        'title' => 'Buku unggulan yang disembunyikan',
        'is_published' => true,
        'is_featured' => true,
        'is_bestseller' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), homepageSettingsPayload([
            'home_sections' => $sections,
        ]))
        ->assertRedirect(route('admin.settings.edit'));

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('services-title', false)
        ->assertDontSee('home-title', false)
        ->assertDontSee('bestsellers-title', false)
        ->assertDontSee('Buku unggulan yang disembunyikan');
});

it('requires an administrator to edit homepage settings', function () {
    $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
    $this->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), homepageSettingsPayload())
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.settings.edit'))
        ->assertForbidden();
    $this->put(route('admin.settings.update'), homepageSettingsPayload())
        ->assertForbidden();

    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'home_hero_title']);
});

it('rejects unsafe CTA destinations and duplicate section ordering without saving settings', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $sections = homepageSettingsPayload()['home_sections'];
    $sections['categories']['order'] = '1';

    $this->actingAs($admin)
        ->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), homepageSettingsPayload([
            'home_hero_cta_url' => 'javascript:alert(1)',
            'home_sections' => $sections,
        ]))
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasErrors(['home_hero_cta_url', 'home_sections']);

    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'home_hero_title']);
});

it('keeps homepage changes in a private draft until an authorized publication', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    SiteSetting::factory()->create([
        'setting_key' => 'home_hero_title',
        'setting_value' => 'Hero yang sudah terbit',
        'is_public' => true,
    ]);

    $draftPayload = homepageSettingsPayload([
        'home_hero_title' => 'Hero pratinjau baru',
        'home_hero_description' => 'Deskripsi draft khusus.',
    ]);
    $this->actingAs(User::factory()->create())
        ->put(route('admin.settings.homepage-draft'), $draftPayload)
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('admin.settings.homepage-draft'), $draftPayload)
        ->assertRedirect(route('admin.settings.edit'));

    $this->assertDatabaseHas('site_settings', [
        'setting_key' => 'home_draft_hero_title',
        'setting_value' => 'Hero pratinjau baru',
        'is_public' => false,
    ]);
    $this->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Simpan Draft')
        ->assertSee('Pratinjau')
        ->assertSee('Publikasikan');
    $this->get(route('home'))->assertOk()->assertSee('Hero yang sudah terbit')->assertDontSee('Hero pratinjau baru');
    expect($this->get(route('sitemap'))->streamedContent())->not->toContain('homepage-preview');

    $preview = $this->get(route('admin.settings.homepage-preview'))
        ->assertOk()
        ->assertSee('Hero pratinjau baru')
        ->assertSee('Deskripsi draft khusus.')
        ->assertSee('Pratinjau admin')
        ->assertSee('noindex,nofollow');
    $preview->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

    $this->put(route('admin.settings.update'), $draftPayload)
        ->assertRedirect(route('admin.settings.edit'));
    $this->get(route('home'))->assertOk()->assertSee('Hero pratinjau baru');
    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'home_draft_hero_title']);
    $this->assertDatabaseHas('site_settings', [
        'setting_key' => 'home_hero_title',
        'setting_value' => 'Hero pratinjau baru',
        'is_public' => true,
    ]);

    $this->get(route('admin.settings.homepage-preview'))->assertNotFound();
});

it('protects the homepage preview and rejects invalid draft data without persisting it', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->get(route('admin.settings.homepage-preview'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())
        ->get(route('admin.settings.homepage-preview'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->from(route('admin.settings.edit'))
        ->put(route('admin.settings.homepage-draft'), homepageSettingsPayload([
            'home_hero_cta_url' => 'javascript:alert(1)',
        ]))
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasErrors('home_hero_cta_url');
    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'home_draft_hero_title']);
});
