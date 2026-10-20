<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createPublishedCatalogProduct(Category $category, array $overrides = []): Product
{
    static $sequence = 0;
    $sequence++;

    return Product::factory()->for($category)->create(array_replace([
        'code' => 'PUB-'.$sequence,
        'slug' => 'public-book-'.$sequence,
        'title' => 'Buku Terbit '.$sequence,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ], $overrides));
}

it('renders homepage sections in the documented order with honest empty states', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Buku untuk menemani pencarian ilmu.')
        ->assertSee('Kategori')
        ->assertSee('Informasi layanan')
        ->assertSee('Buku bestseller')
        ->assertSee('Katalog buku')
        ->assertSee('Artikel terbaru')
        ->assertSee('Belum ada produk yang diterbitkan.')
        ->assertDontSee('Keranjang')
        ->assertDontSee('Checkout')
        ->assertSee(route('home'), false)
        ->assertSee('"@type":"Organization"', false);

    $body = $response->getContent();
    expect(strpos($body, 'home-title'))
        ->toBeLessThan(strpos($body, 'categories-title'))
        ->toBeLessThan(strpos($body, 'services-title'))
        ->toBeLessThan(strpos($body, 'bestsellers-title'))
        ->toBeLessThan(strpos($body, 'books-title'))
        ->toBeLessThan(strpos($body, 'latest-articles-title'));
});

it('renders published featured books as accessible hero carousel slides', function () {
    $category = Category::factory()->create(['is_published' => true]);
    createPublishedCatalogProduct($category, [
        'title' => 'Buku Unggulan Pertama',
        'is_featured' => true,
        'published_at' => now()->subDay(),
    ]);
    createPublishedCatalogProduct($category, [
        'title' => 'Buku Unggulan Kedua',
        'is_featured' => true,
        'published_at' => now()->subDays(2),
    ]);
    createPublishedCatalogProduct($category, [
        'title' => 'Buku Biasa',
        'is_featured' => false,
    ]);
    createPublishedCatalogProduct($category, [
        'title' => 'Buku Unggulan Draft',
        'is_featured' => true,
        'is_published' => false,
    ]);

    $response = $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-featured-carousel', false)
        ->assertSee('aria-roledescription="carousel"', false)
        ->assertSee('aria-label="1 dari 2"', false)
        ->assertSee('aria-label="2 dari 2" hidden', false)
        ->assertSee('data-featured-previous', false)
        ->assertSee('data-featured-next', false)
        ->assertSee('data-featured-rotation', false)
        ->assertSee('Buku Unggulan Pertama')
        ->assertSee('Buku Unggulan Kedua')
        ->assertDontSee('Buku Unggulan Draft');

    expect(substr_count($response->getContent(), 'data-featured-slide'))->toBe(2);
});

it('shows only published products and articles in public sections', function () {
    $category = Category::factory()->create(['is_published' => true]);
    createPublishedCatalogProduct($category, [
        'title' => 'Buku yang Terbit',
        'is_featured' => true,
        'is_bestseller' => true,
    ]);
    createPublishedCatalogProduct($category, [
        'title' => 'Buku Draft Rahasia',
        'is_published' => false,
    ]);
    createPublishedCatalogProduct($category, [
        'title' => 'Buku Terjadwal',
        'published_at' => now()->addDay(),
    ]);
    Article::factory()->create([
        'title' => 'Artikel Terbit',
        'slug' => 'artikel-terbit',
        'status' => ArticleStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    Article::factory()->create([
        'title' => 'Artikel Draft Rahasia',
        'slug' => 'artikel-draft-rahasia',
        'status' => ArticleStatus::Draft,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Buku yang Terbit')
        ->assertSee('Artikel Terbit')
        ->assertDontSee('Buku Draft Rahasia')
        ->assertDontSee('Buku Terjadwal')
        ->assertDontSee('Artikel Draft Rahasia');

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Artikel Terbit')
        ->assertDontSee('Artikel Draft Rahasia');
    $this->get(route('articles.show', ['article' => 'artikel-terbit']))
        ->assertOk()
        ->assertSee('"@type":"Article"', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('"dateModified":', false)
        ->assertSee('"publisher":{"@type":"Organization"', false);
});

it('filters, sorts and paginates with allowlisted catalog inputs', function () {
    $category = Category::factory()->create(['is_published' => true, 'slug' => 'fiqh']);
    $otherCategory = Category::factory()->create(['is_published' => true, 'slug' => 'adab']);

    foreach (range(1, 13) as $number) {
        createPublishedCatalogProduct($category, [
            'title' => 'Kitab Fiqh '.$number,
            'unit_price' => 1000 + $number,
        ]);
    }

    createPublishedCatalogProduct($otherCategory, ['title' => 'Kitab Adab']);

    $isMySql = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

    if ($isMySql) {
        DB::commit();
    }

    try {
        if ($isMySql) {
            expect(
                Product::query()
                    ->published()
                    ->whereFullText(['title', 'author', 'publisher'], 'Fiqh*', ['mode' => 'boolean'])
                    ->whereHas('category', fn ($query) => $query->where('slug', 'fiqh'))
                    ->count()
            )->toBe(13);
        }

        $this->get(route('products.index', [
            'q' => 'Fiqh',
            'category' => 'fiqh',
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSee('Kitab Fiqh 1')
            ->assertDontSee('Kitab Adab')
            ->assertSee('q=Fiqh')
            ->assertSee('category=fiqh')
            ->assertSee('sort=price_asc');

        $secondPage = $this->get(route('products.index', [
            'q' => 'Fiqh',
            'category' => 'fiqh',
            'sort' => 'price_asc',
            'page' => 2,
        ]));
        $secondPage->assertOk()->assertSee('Kitab Fiqh 13')->assertDontSee('Kitab Adab');

        $this->get(route('products.index', ['sort' => 'unit_price desc']))
            ->assertRedirect(route('products.index'));

        $this->get(route('products.index', ['q' => 'Fiqh']))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false)
            ->assertSee('<link rel="canonical" href="'.route('products.index').'"', false);

        $this->get(route('products.index', ['q' => 'ISBN-tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada buku yang cocok dengan pencarian')
            ->assertSee(route('products.index'), false)
            ->assertSee('Hapus filter');
    } finally {
        if ($isMySql) {
            Product::query()->whereIn('category_id', [$category->getKey(), $otherCategory->getKey()])->delete();
            $category->delete();
            $otherCategory->delete();
        }
    }
});

it('renders published book details, gallery, breadcrumb and seo fields only', function () {
    $category = Category::factory()->create(['is_published' => true, 'slug' => 'fiqh']);
    $product = createPublishedCatalogProduct($category, [
        'slug' => 'buku-detail',
        'title' => 'Buku Detail Publik',
        'description' => '<script>alert(1)</script>Deskripsi buku.',
        'meta_title' => 'SEO Buku Detail',
        'meta_description' => 'Deskripsi SEO buku detail.',
        'stock' => 7,
    ]);
    $imagePath = 'products/'.$product->id.'/'.Str::uuid().'/320.webp';
    ProductImage::factory()->for($product)->create([
        'disk' => 'public',
        'path' => $imagePath,
        'alt_text' => 'Sampul buku detail',
        'variants' => [320 => $imagePath],
        'width' => 320,
        'height' => 480,
        'sort_order' => 0,
    ]);
    $secondaryImagePath = 'products/'.$product->id.'/'.Str::uuid().'/640.webp';
    ProductImage::factory()->for($product)->create([
        'disk' => 'public',
        'path' => $secondaryImagePath,
        'alt_text' => 'Foto kedua buku detail',
        'variants' => [320 => $secondaryImagePath, 640 => $secondaryImagePath],
        'width' => 640,
        'height' => 960,
        'sort_order' => 1,
    ]);

    $response = $this->get(route('products.show', ['product' => $product->slug]));
    $response->assertOk()
        ->assertSee('Buku Detail Publik')
        ->assertSee('Sampul buku detail')
        ->assertSee('role="group" aria-label="Pilih foto produk"', false)
        ->assertSee('data-product-gallery-main', false)
        ->assertSee('data-product-gallery-thumb', false)
        ->assertSee('aria-pressed="true"', false)
        ->assertSee('aria-label="Tampilkan foto 2: Foto kedua buku detail"', false)
        ->assertSee('Stok tercatat: 7')
        ->assertSee('SEO Buku Detail')
        ->assertSee(route('products.show', ['product' => $product->slug]), false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);

    $draft = createPublishedCatalogProduct($category, ['is_published' => false, 'slug' => 'buku-draft-private']);
    $this->get(route('products.show', ['product' => $draft->slug]))->assertNotFound();
});

it('searches by ISBN and clearly labels unit and wholesale prices', function () {
    $category = Category::factory()->create(['is_published' => true]);
    $product = createPublishedCatalogProduct($category, [
        'title' => 'Panduan Harga Buku',
        'isbn' => '9781234567890',
        'unit_price' => 125000,
        'wholesale_price' => 100000,
        'wholesale_minimum' => 5,
    ]);
    SiteSetting::factory()->create([
        'setting_key' => 'whatsapp_number',
        'setting_value' => '+6281234567890',
        'is_public' => true,
    ]);

    $this->get(route('products.index', ['q' => '9781234567890']))
        ->assertOk()
        ->assertSee('Panduan Harga Buku');
    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertSee('Harga satuan')
        ->assertSee('Rp125.000')
        ->assertSee('Harga grosir')
        ->assertSee('Rp100.000')
        ->assertSee('Berlaku mulai 5 buku.')
        ->assertSee('Tanyakan buku via WhatsApp');
});

it('uses unique pagination metadata and list breadcrumbs for public articles', function () {
    foreach (range(1, 13) as $number) {
        Article::factory()->create([
            'title' => 'Artikel Halaman '.$number,
            'slug' => 'artikel-halaman-'.$number,
            'status' => ArticleStatus::Published,
            'published_at' => now()->subMinutes($number),
        ]);
    }

    $this->get(route('articles.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('<title>Artikel - Halaman 2 | Toko Buku Alfikar</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('articles.index', ['page' => 2]).'"', false)
        ->assertSee('<meta name="robots" content="index,follow">', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('Artikel Halaman 13')
        ->assertDontSee('Artikel Halaman 1</a>');

    $this->get(route('articles.index', ['utm_source' => 'newsletter']))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('articles.index').'"', false)
        ->assertSee('<meta name="robots" content="noindex,follow">', false);

    $this->get(route('articles.index', ['page' => 'invalid']))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex,follow">', false);

    $this->get(route('articles.index', ['page' => 3]))->assertNotFound();
    $this->get(route('products.index', ['page' => 3]))->assertNotFound();
});

it('avoids duplicate detail lookups and author N plus one queries', function () {
    $author = User::factory()->create();
    foreach (range(1, 15) as $number) {
        Article::factory()->create([
            'author_id' => $author->id,
            'title' => 'Query article '.$number,
            'slug' => 'query-article-'.$number,
            'status' => ArticleStatus::Published,
            'published_at' => now()->subMinutes($number),
        ]);
    }

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->get(route('articles.index'))->assertOk();
    $userSelects = array_filter($queries, static fn (string $sql): bool => preg_match('/from\s+(?:"|`)?users(?:"|`)?/i', $sql) === 1);
    $articleSelects = array_filter($queries, static fn (string $sql): bool => preg_match('/from\s+(?:"|`)?articles(?:"|`)?/i', $sql) === 1);

    expect($userSelects)->toHaveCount(1)
        ->and($articleSelects)->toHaveCount(2);

    $queries = [];
    $this->get(route('articles.show', 'query-article-1'))->assertOk();
    $articleDetailSelects = array_filter($queries, static fn (string $sql): bool => preg_match('/from\s+(?:"|`)?articles(?:"|`)?/i', $sql) === 1);
    expect($articleDetailSelects)->toHaveCount(1);

    $category = Category::factory()->create(['is_published' => true]);
    $product = createPublishedCatalogProduct($category, ['slug' => 'single-detail-query']);
    $queries = [];

    $this->get(route('products.show', $product->slug))->assertOk();
    $productSelects = array_filter($queries, static fn (string $sql): bool => preg_match('/from\s+(?:"|`)?products(?:"|`)?/i', $sql) === 1);

    expect($productSelects)->toHaveCount(2);
});

it('returns 404 for unknown public article and product slugs', function () {
    $this->get(route('articles.show', 'artikel-tidak-ada'))->assertNotFound();
    $this->get(route('products.show', 'buku-tidak-ada'))->assertNotFound();
});

it('builds fixed-host WhatsApp messages with wholesale threshold and never changes stock', function () {
    SiteSetting::factory()->create([
        'setting_key' => 'whatsapp_number',
        'setting_value' => '+6281234567890',
        'is_public' => true,
    ]);
    $category = Category::factory()->create(['is_published' => true]);
    $product = createPublishedCatalogProduct($category, [
        'slug' => 'buku-wholesale',
        'title' => 'Buku Grosir',
        'code' => 'BK-WA-01',
        'stock' => 20,
        'unit_price' => 10000,
        'wholesale_price' => 8000,
        'wholesale_minimum' => 5,
    ]);
    $stockBefore = $product->stock;

    $single = $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 4]))
        ->assertRedirect();
    $singleUrl = $single->headers->get('Location');
    parse_str((string) parse_url($singleUrl, PHP_URL_QUERY), $singleQuery);
    expect(parse_url($singleUrl, PHP_URL_SCHEME))->toBe('https')
        ->and(parse_url($singleUrl, PHP_URL_HOST))->toBe('wa.me')
        ->and($singleQuery['text'])->toContain('Kode: BK-WA-01')
        ->and($singleQuery['text'])->toContain('Jumlah: 4')
        ->and($singleQuery['text'])->toContain('Harga satuan: Rp10.000')
        ->and($singleQuery['text'])->toContain('Subtotal: Rp40.000 (belum termasuk ongkir)')
        ->and($singleQuery['text'])->toContain(route('products.show', ['product' => $product->slug]))
        ->and($singleQuery['text'])->toContain('konfirmasi stok dan ongkir');

    $wholesale = $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 5]))
        ->assertRedirect();
    parse_str((string) parse_url($wholesale->headers->get('Location'), PHP_URL_QUERY), $wholesaleQuery);
    expect($wholesaleQuery['text'])->toContain('Harga satuan: Rp8.000')
        ->and($wholesaleQuery['text'])->toContain('Subtotal: Rp40.000 (belum termasuk ongkir)');

    $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 0]))
        ->assertRedirect(route('products.show', ['product' => $product->slug]))
        ->assertSessionHasErrors('quantity');
    expect($product->fresh()->stock)->toBe($stockBefore);
    $this->assertDatabaseCount('audit_logs', 0);
    $this->assertDatabaseCount('products', 1);
});

it('asks availability for out of stock and disables WhatsApp for invalid numbers', function () {
    SiteSetting::factory()->create([
        'setting_key' => 'whatsapp_number',
        'setting_value' => '+6281234567890',
        'is_public' => true,
    ]);
    $category = Category::factory()->create(['is_published' => true]);
    $product = createPublishedCatalogProduct($category, [
        'slug' => 'stok-kosong',
        'stock' => 0,
        'unit_price' => 12000,
    ]);

    $response = $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 1]))
        ->assertRedirect();
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    expect($query['text'])->toContain('Mohon informasikan apakah buku tersedia kembali.');
    expect($product->fresh()->stock)->toBe(0);

    SiteSetting::query()->where('setting_key', 'whatsapp_number')->update(['setting_value' => '62812-invalid']);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertSee('WhatsApp belum tersedia')
        ->assertDontSee(route('products.whatsapp', ['product' => $product->slug]), false);
    $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 1]))->assertNotFound();
});

it('shows only public allowlisted profile and validated contact links', function () {
    SiteSetting::factory()->create([
        'setting_key' => 'profile_description',
        'setting_value' => 'Profil Alfikar.',
        'is_public' => true,
    ]);
    SiteSetting::factory()->create([
        'setting_key' => 'contact_address',
        'setting_value' => 'Alamat Alfikar.',
        'is_public' => true,
    ]);
    SiteSetting::factory()->create([
        'setting_key' => 'social_instagram',
        'setting_value' => 'https://social.example/alfikar',
        'is_public' => true,
    ]);
    SiteSetting::factory()->create([
        'setting_key' => 'social_facebook',
        'setting_value' => 'javascript:alert(1)',
        'is_public' => true,
    ]);
    SiteSetting::factory()->create([
        'setting_key' => 'contact_phone',
        'setting_value' => 'PRIVATE-NOT-PUBLIC',
        'is_public' => false,
    ]);

    $this->get(route('pages.about'))->assertOk()->assertSee('Profil Alfikar.');
    $this->get(route('pages.contact'))
        ->assertOk()
        ->assertSee('Alamat Alfikar.')
        ->assertSee('https://social.example/alfikar')
        ->assertDontSee('javascript:alert(1)', false)
        ->assertDontSee('PRIVATE-NOT-PUBLIC');
});
