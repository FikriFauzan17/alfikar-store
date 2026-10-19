<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ProductImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function adminCatalogProductPayload(Category $category, array $overrides = []): array
{
    return array_replace([
        'category_id' => $category->id,
        'code' => 'BK-ALF-0001',
        'slug' => 'buku-pilihan-alfikar',
        'title' => 'Buku Pilihan Alfikar',
        'author' => 'Penulis Alfikar',
        'publisher' => 'Penerbit Alfikar',
        'isbn' => null,
        'description' => 'Deskripsi buku yang sudah diperiksa.',
        'specifications_json' => '{"halaman":"180","format":"A5"}',
        'stock' => 14,
        'unit_price' => 120000,
        'wholesale_price' => 95000,
        'wholesale_minimum' => 10,
        'is_published' => '1',
        'is_featured' => '1',
        'is_bestseller' => '0',
        'published_at' => null,
        'meta_title' => 'Buku Pilihan Alfikar',
        'meta_description' => 'Informasi buku pilihan.',
    ], $overrides);
}

function makeAdminCatalogPngUpload(int $width = 60, int $height = 90): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 4, 78, 59));
    imagefilledrectangle($image, 10, 10, $width - 10, $height - 10, imagecolorallocate($image, 198, 167, 94));

    ob_start();
    $encoded = imagepng($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    if (! $encoded || ! is_string($contents)) {
        throw new RuntimeException('Unable to create a catalog image fixture.');
    }

    return UploadedFile::fake()->createWithContent('cover.png', $contents);
}

it('requires administrator authorization for all catalog resources', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    $this->get(route('admin.products.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());

    $this->get(route('admin.categories.index'))->assertForbidden();
    $this->get(route('admin.products.edit', $product))->assertForbidden();
    $this->post(route('admin.products.images.store', $product), [
        'images' => [UploadedFile::fake()->createWithContent('file.png', 'not an image')],
    ])->assertForbidden();

    $this->assertDatabaseCount('audit_logs', 0);
});

it('creates, updates, lists, confirms and deletes categories', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.categories.create'))->assertOk();
    $this->post(route('admin.categories.store'), [
        'name' => 'Bahasa Arab',
        'description' => 'Kategori pilihan.',
        'sort_order' => 2,
        'is_published' => '1',
    ])->assertRedirect(route('admin.categories.index'));

    $category = Category::query()->where('slug', 'bahasa-arab')->firstOrFail();
    expect($category->is_published)->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['event' => 'category.created', 'subject_id' => $category->id]);
    $this->get(route('admin.categories.index'))->assertOk()->assertSee('Bahasa Arab');
    $this->get(route('admin.categories.edit', $category))->assertOk();

    $this->put(route('admin.categories.update', $category), [
        'name' => 'Fiqh',
        'slug' => 'bahasa-arab',
        'description' => 'Kategori diperbarui.',
        'sort_order' => 1,
        'is_published' => '0',
    ])->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->name)->toBe('Fiqh')
        ->and($category->fresh()->slug)->toBe('bahasa-arab')
        ->and($category->fresh()->is_published)->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['event' => 'category.updated', 'subject_id' => $category->id]);

    $this->get(route('admin.categories.confirm-delete', $category))->assertOk();
    $this->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.categories.index'));
    $this->assertModelMissing($category);
    $this->assertDatabaseHas('audit_logs', ['event' => 'category.deleted', 'subject_id' => $category->id]);
});

it('stores one optimized category image, displays it publicly, and replaces or removes it', function () {
    Storage::fake(config('image-processing.disk', 'public'));
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.categories.store'), [
        'name' => 'Bahasa Arab',
        'description' => 'Kategori pilihan.',
        'sort_order' => 2,
        'is_published' => '1',
        'image' => makeAdminCatalogPngUpload(),
    ])->assertRedirect(route('admin.categories.index'));

    $category = Category::query()->where('slug', 'bahasa-arab')->firstOrFail();
    $firstImagePath = $category->image_path;
    expect($firstImagePath)->toBeString()->toEndWith('.webp');
    Storage::disk(config('image-processing.disk', 'public'))->assertExists($firstImagePath);
    $this->get(route('home'))->assertOk()->assertSee($firstImagePath);

    $this->get(route('admin.categories.edit', $category))
        ->assertOk()
        ->assertSee('Gambar kategori (satu gambar)');

    $this->put(route('admin.categories.update', $category), [
        'name' => 'Bahasa Arab',
        'slug' => 'bahasa-arab',
        'description' => 'Kategori pilihan.',
        'sort_order' => 2,
        'is_published' => '1',
        'image' => makeAdminCatalogPngUpload(80, 90),
    ])->assertRedirect(route('admin.categories.index'));

    $secondImagePath = $category->fresh()->image_path;
    expect($secondImagePath)->not->toBe($firstImagePath);
    Storage::disk(config('image-processing.disk', 'public'))->assertMissing($firstImagePath);
    Storage::disk(config('image-processing.disk', 'public'))->assertExists($secondImagePath);

    $this->put(route('admin.categories.update', $category), [
        'name' => 'Bahasa Arab',
        'slug' => 'bahasa-arab',
        'description' => 'Kategori pilihan.',
        'sort_order' => 2,
        'is_published' => '1',
        'remove_image' => '1',
    ])->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->image_path)->toBeNull();
    Storage::disk(config('image-processing.disk', 'public'))->assertMissing($secondImagePath);
});

it('uses a published product cover as a square category image on the homepage', function () {
    Storage::fake(config('image-processing.disk', 'public'));
    $category = Category::factory()->create(['is_published' => true]);
    Product::factory()->for($category)->create([
        'is_published' => true,
        'published_at' => now(),
    ]);
    $product = Product::factory()->for($category)->create([
        'is_published' => true,
        'published_at' => now()->subMinute(),
    ]);
    ProductImage::factory()->for($product)->create([
        'path' => 'products/category-cover.webp',
        'variants' => [320 => 'products/category-cover-320.webp'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('/storage/products/category-cover-320.webp', false)
        ->assertSee('aspect-square', false);
});

it('rejects unsupported category image uploads without creating the category', function () {
    Storage::fake(config('image-processing.disk', 'public'));
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.categories.create'))
        ->post(route('admin.categories.store'), [
            'name' => 'Kategori Tidak Sah',
            'is_published' => '1',
            'image' => UploadedFile::fake()->create('category.svg', 20, 'image/svg+xml'),
        ])
        ->assertRedirect(route('admin.categories.create'))
        ->assertSessionHasErrors('image');

    $this->assertDatabaseMissing('categories', ['slug' => 'kategori-tidak-sah']);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('rejects invalid and duplicate category slugs without writing records', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);
    Category::factory()->create(['slug' => 'fiqh']);

    $this->from(route('admin.categories.create'))
        ->post(route('admin.categories.store'), [
            'name' => 'Second Fiqh',
            'slug' => 'fiqh',
            'sort_order' => -1,
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.categories.create'))
        ->assertSessionHasErrors(['slug', 'sort_order']);

    $this->assertDatabaseCount('categories', 1);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('prevents deleting a category that is referenced by a product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect();

    $this->assertModelExists($category);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('creates and updates product inventory, pricing, publication and admin flags', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    $this->actingAs($admin);

    $this->get(route('admin.products.create'))->assertOk();
    $this->post(route('admin.products.store'), adminCatalogProductPayload($category))
        ->assertRedirect(route('admin.products.edit', Product::query()->first()));

    $product = Product::query()->where('code', 'BK-ALF-0001')->firstOrFail();
    expect($product->specifications)->toBe(['halaman' => '180', 'format' => 'A5'])
        ->and($product->unit_price)->toBe(120000)
        ->and($product->wholesale_price)->toBe(95000)
        ->and($product->wholesale_minimum)->toBe(10)
        ->and($product->is_bestseller)->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['event' => 'product.created', 'subject_id' => $product->id]);
    $this->get(route('admin.products.index'))->assertOk()->assertSee('Buku Pilihan Alfikar');
    $this->get(route('admin.products.edit', $product))->assertOk();

    $this->put(route('admin.products.update', $product), adminCatalogProductPayload($category, [
        'stock' => 31,
        'unit_price' => 130000,
        'wholesale_price' => 100000,
        'wholesale_minimum' => 8,
        'is_published' => '0',
        'is_featured' => '0',
        'is_bestseller' => '1',
    ]))->assertRedirect(route('admin.products.edit', $product));

    $product->refresh();
    expect($product->stock)->toBe(14)
        ->and($product->unit_price)->toBe(130000)
        ->and($product->wholesale_price)->toBe(100000)
        ->and($product->wholesale_minimum)->toBe(8)
        ->and($product->is_published)->toBeFalse()
        ->and($product->is_featured)->toBeFalse()
        ->and($product->is_bestseller)->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['event' => 'product.updated', 'subject_id' => $product->id]);
});

it('rejects invalid wholesale pairs and duplicate product codes or slugs', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create([
        'code' => 'BK-ALF-0001',
        'slug' => 'existing-book',
    ]);
    Product::factory()->for($category)->create([
        'code' => 'BK-ALF-0002',
        'slug' => 'another-book',
    ]);
    $this->actingAs($admin);

    $this->from(route('admin.products.create'))
        ->post(route('admin.products.store'), adminCatalogProductPayload($category, [
            'code' => 'BK-ALF-0001',
            'slug' => 'another-book',
            'wholesale_price' => 150000,
            'wholesale_minimum' => 10,
        ]))
        ->assertRedirect(route('admin.products.create'))
        ->assertSessionHasErrors(['code', 'wholesale_price']);

    $this->from(route('admin.products.edit', $product))
        ->put(route('admin.products.update', $product), adminCatalogProductPayload($category, [
            'code' => 'BK-ALF-0001',
            'slug' => 'another-book',
            'wholesale_price' => null,
            'wholesale_minimum' => 10,
        ]))
        ->assertRedirect(route('admin.products.edit', $product))
        ->assertSessionHasErrors(['slug', 'wholesale_price']);

    $this->from(route('admin.products.create'))
        ->post(route('admin.products.store'), adminCatalogProductPayload($category, [
            'code' => 'BK-ALF-0003',
            'slug' => 'nested-specification-book',
            'specifications_json' => '{"dimensions":{"width":20}}',
        ]))
        ->assertRedirect(route('admin.products.create'))
        ->assertSessionHasErrors('specifications_json');

    $this->assertDatabaseCount('products', 2);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('redirects old product slugs only while the canonical product is public', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create(['is_published' => true]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'buku-sebelum',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $this->actingAs($admin);

    $this->put(route('admin.products.update', $product), adminCatalogProductPayload($category, [
        'code' => $product->code,
        'slug' => 'buku-saat-ini',
        'title' => $product->title,
        'is_published' => '1',
        'is_featured' => '0',
        'is_bestseller' => '0',
    ]))->assertRedirect(route('admin.products.edit', $product));

    expect($product->fresh()->slug)->toBe('buku-saat-ini');
    $this->assertDatabaseHas('product_slug_redirects', [
        'product_id' => $product->id,
        'slug' => 'buku-sebelum',
    ]);
    $this->get(route('products.show', 'buku-sebelum'))
        ->assertMovedPermanently()
        ->assertRedirect(route('products.show', 'buku-saat-ini'));
    $this->get(route('products.show', 'buku-saat-ini'))
        ->assertOk()
        ->assertSee('rel="canonical" href="'.route('products.show', 'buku-saat-ini').'"', false);

    $this->from(route('admin.products.create'))
        ->post(route('admin.products.store'), adminCatalogProductPayload($category, [
            'code' => 'BK-ALF-ALIAS',
            'slug' => 'buku-sebelum',
        ]))
        ->assertRedirect(route('admin.products.create'))
        ->assertSessionHasErrors('slug');
    $this->assertDatabaseCount('products', 1);

    $this->put(route('admin.products.update', $product), adminCatalogProductPayload($category, [
        'code' => $product->code,
        'slug' => 'buku-saat-ini',
        'title' => $product->title,
        'is_published' => '0',
        'is_featured' => '0',
        'is_bestseller' => '0',
    ]))->assertRedirect(route('admin.products.edit', $product));

    $this->get(route('products.show', 'buku-sebelum'))->assertNotFound();
    $this->get(route('products.show', 'buku-saat-ini'))->assertNotFound();
});

it('uploads a gallery, chooses the main photo by order and audits each image', function () {
    if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
        $this->markTestSkipped('GD with WebP and EXIF support is required for image processing tests.');
    }

    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $this->actingAs($admin);

    $this->post(route('admin.products.images.store', $product), [
        'images' => [makeAdminCatalogPngUpload(), makeAdminCatalogPngUpload(80, 100)],
    ])->assertRedirect(route('admin.products.edit', $product));

    $images = $product->images()->orderBy('sort_order')->get();
    expect($images)->toHaveCount(2)
        ->and($images[0]->sort_order)->toBe(0)
        ->and($images[1]->sort_order)->toBe(1)
        ->and($images[0]->path)->toBe($images[0]->variants[60])
        ->and(Storage::disk('public')->allFiles())->toHaveCount(2);
    $this->assertDatabaseCount('audit_logs', 2);

    $this->patch(route('admin.products.images.order', $product), [
        'image_order' => [$images[0]->id => 1, $images[1]->id => 0],
    ])->assertRedirect(route('admin.products.edit', $product));

    expect($product->images()->orderBy('sort_order')->first()->getKey())->toBe($images[1]->getKey());
    $this->assertDatabaseHas('audit_logs', ['event' => 'product.images.reordered', 'subject_id' => $product->id]);
});

it('returns actual source and processed image metadata for per-file uploads', function () {
    if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
        $this->markTestSkipped('GD with WebP and EXIF support is required for image processing tests.');
    }

    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $upload = makeAdminCatalogPngUpload();
    $inputSize = $upload->getSize();
    $this->actingAs($admin);

    $response = $this->post(route('admin.products.images.store', $product), [
        'images' => [$upload],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('images.0.input_size', $inputSize)
        ->assertJsonPath('images.0.width', 60)
        ->assertJsonPath('images.0.height', 90)
        ->assertJsonPath('images.0.mime_type', 'image/webp');

    $image = $product->images()->sole();
    expect($image->file_size)->toBeGreaterThan(0)
        ->and($response->json('images.0.stored_size'))->toBe($image->file_size);
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'product.image.uploaded',
        'subject_id' => $image->id,
    ]);

    $this->post(route('admin.products.images.store', $product), [
        'images' => [UploadedFile::fake()->createWithContent('spoof.png', '<script>invalid image</script>')],
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    $this->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('Ukuran hasil tersimpan (semua varian)')
        ->assertSee('data-product-image-primary-action', false)
        ->assertSee('data-product-image-upload', false)
        ->assertSee('data-image-limit="5"', false);
    expect($product->images()->count())->toBe(1);
});

it('keeps gallery ordering controls available while hiding uploads at the five-photo limit', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    ProductImage::factory()->count(5)->for($product)->sequence(
        ['sort_order' => 0],
        ['sort_order' => 1],
        ['sort_order' => 2],
        ['sort_order' => 3],
        ['sort_order' => 4],
    )->create();

    $this->actingAs($admin)
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('data-product-image-order-form', false)
        ->assertSee('data-product-image-move="up"', false)
        ->assertSee('data-product-image-move="down"', false)
        ->assertSee('data-product-image-primary-action', false)
        ->assertDontSee('data-product-image-upload', false);

    expect($product->images()->count())->toBe(5);
});

it('rejects spoofed uploads and gallery overflow without persisting a sixth image', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD is required to create a valid PNG upload for the gallery limit test.');
    }

    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    ProductImage::factory()->count(4)->for($product)->sequence(
        ['sort_order' => 0],
        ['sort_order' => 1],
        ['sort_order' => 2],
        ['sort_order' => 3],
    )->create();
    Storage::fake('public');
    $this->actingAs($admin);

    $this->from(route('admin.products.edit', $product))
        ->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->createWithContent('spoof.png', '<script>not an image</script>')],
        ])
        ->assertRedirect(route('admin.products.edit', $product))
        ->assertSessionHasErrors('image');

    ProductImage::factory()->for($product)->create(['sort_order' => 4]);

    $this->from(route('admin.products.edit', $product))
        ->post(route('admin.products.images.store', $product), [
            'images' => [makeAdminCatalogPngUpload()],
        ])
        ->assertRedirect(route('admin.products.edit', $product))
        ->assertSessionHasErrors('images');

    expect($product->images()->count())->toBe(5)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('deletes a confirmed primary photo and promotes the next ordered image', function () {
    if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
        $this->markTestSkipped('GD with WebP and EXIF support is required for image processing tests.');
    }

    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $processor = app(ProductImageProcessor::class);
    $primary = $processor->storeFor($product, makeAdminCatalogPngUpload(60, 90), 'Foto utama');
    $secondary = $processor->storeFor($product, makeAdminCatalogPngUpload(80, 100), 'Foto kedua');
    $primaryPath = $primary->path;
    $this->actingAs($admin);

    $this->get(route('admin.products.images.confirm-delete', [$product, $primary]))->assertOk();
    $this->delete(route('admin.products.images.destroy', [$product, $primary]))
        ->assertRedirect(route('admin.products.edit', $product));

    expect($product->images()->count())->toBe(1)
        ->and($product->images()->first()->getKey())->toBe($secondary->getKey())
        ->and($product->images()->first()->sort_order)->toBe(0)
        ->and(Storage::disk('public')->exists($primaryPath))->toBeFalse()
        ->and(Storage::disk('public')->exists($secondary->path))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['event' => 'product.image.deleted', 'subject_id' => $primary->id]);
});

it('rolls back earlier batch photos and audit entries when a later image exceeds resource limits', function () {
    if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
        $this->markTestSkipped('GD with WebP and EXIF support is required for image processing tests.');
    }

    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $this->actingAs($admin);
    config(['image-processing.max_pixels' => 4000]);

    $this->from(route('admin.products.edit', $product))
        ->post(route('admin.products.images.store', $product), [
            'images' => [makeAdminCatalogPngUpload(40, 50), makeAdminCatalogPngUpload(80, 100)],
        ])
        ->assertRedirect(route('admin.products.edit', $product))
        ->assertSessionHasErrors('image');

    expect($product->images()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('scopes photo confirmation and deletion to its owning product', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $firstProduct = Product::factory()->create();
    $secondProduct = Product::factory()->create();
    $foreignImage = ProductImage::factory()->for($secondProduct)->create([
        'path' => 'products/'.$secondProduct->id.'/'.Str::uuid().'/640.webp',
        'variants' => [],
    ]);
    $this->actingAs($admin);

    $this->get(route('admin.products.images.confirm-delete', [$firstProduct, $foreignImage]))->assertNotFound();
    $this->delete(route('admin.products.images.destroy', [$firstProduct, $foreignImage]))->assertNotFound();
    $this->from(route('admin.products.edit', $firstProduct))
        ->patch(route('admin.products.images.order', $firstProduct), [
            'image_order' => [$foreignImage->id => 0],
        ])
        ->assertRedirect(route('admin.products.edit', $firstProduct))
        ->assertSessionHasErrors('image_order');
    $this->assertModelExists($foreignImage);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('confirms product deletion and removes only its managed photo files', function () {
    if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
        $this->markTestSkipped('GD with WebP and EXIF support is required for image processing tests.');
    }

    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $image = app(ProductImageProcessor::class)->storeFor($product, makeAdminCatalogPngUpload());
    $managedPaths = array_values($image->variants);
    Storage::disk('public')->put('unrelated.txt', 'keep');
    $this->actingAs($admin);

    $this->get(route('admin.products.confirm-delete', $product))->assertOk()->assertSee('1 foto');
    $this->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));

    $this->assertModelMissing($product);
    $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    $this->assertDatabaseHas('audit_logs', ['event' => 'product.deleted', 'subject_id' => $product->id]);
    expect(Storage::disk('public')->exists('unrelated.txt'))->toBeTrue();

    foreach ($managedPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }
});

it('does not seed catalog or administrator records', function () {
    $this->seed();

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('categories', 0);
    $this->assertDatabaseCount('products', 0);
});

it('keeps product drafts private and exposes an administrator-only uncached preview until publication', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create(['is_published' => true]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'preview-draft-book',
        'title' => 'Buku Draft untuk Pratinjau',
        'is_published' => false,
    ]);

    $this->get(route('admin.products.preview', $product))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.products.preview', $product))->assertForbidden();

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), adminCatalogProductPayload($category, [
            'publication_action' => 'draft',
            'is_published' => '1',
            'slug' => 'preview-draft-book',
            'title' => 'Buku Draft untuk Pratinjau',
        ]))
        ->assertRedirect(route('admin.products.edit', $product));

    $preview = $this->get(route('admin.products.preview', $product))
        ->assertOk()
        ->assertSee('Buku Draft untuk Pratinjau')
        ->assertSee('Pratinjau admin')
        ->assertSee('noindex,nofollow')
        ->assertDontSee('Pesan via WhatsApp');
    $preview->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('Simpan Draft')
        ->assertSee('Pratinjau')
        ->assertSee('Publikasikan');
    $this->get(route('products.show', $product->slug))->assertNotFound();
    expect($this->get(route('sitemap'))->streamedContent())->not->toContain('preview-draft-book');
    expect($product->fresh()->is_published)->toBeFalse();

    $this->from(route('admin.products.edit', $product))
        ->put(route('admin.products.update', $product), adminCatalogProductPayload($category, [
            'publication_action' => 'publish',
            'is_published' => '0',
            'slug' => 'preview-draft-book',
            'unit_price' => '-1',
        ]))
        ->assertRedirect(route('admin.products.edit', $product))
        ->assertSessionHasErrors('unit_price');
    expect($product->fresh()->is_published)->toBeFalse();

    $this->put(route('admin.products.update', $product), adminCatalogProductPayload($category, [
        'publication_action' => 'publish',
        'is_published' => '0',
        'slug' => 'preview-draft-book',
        'title' => 'Buku Draft untuk Pratinjau',
    ]))->assertRedirect(route('admin.products.edit', $product));

    expect($product->fresh()->is_published)->toBeTrue();
    $this->get(route('products.show', $product->slug))->assertOk()->assertSee('Buku Draft untuk Pratinjau');
    expect($this->get(route('sitemap'))->streamedContent())->toContain('preview-draft-book');
});
