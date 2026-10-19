<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDailyMetric;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ProductMetricRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function metricPublishedProduct(array $overrides = []): Product
{
    $category = Category::factory()->create(['is_published' => true]);

    return Product::factory()->for($category)->create(array_replace([
        'slug' => 'metric-product',
        'title' => 'Buku Statistik',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ], $overrides));
}

it('records product visits and valid WhatsApp redirects without changing stock', function () {
    $product = metricPublishedProduct(['stock' => 9]);
    SiteSetting::factory()->create([
        'setting_key' => 'whatsapp_number',
        'setting_value' => '+6281234567890',
        'is_public' => true,
    ]);

    $this->get(route('products.show', $product->slug))->assertOk();
    $response = $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 2]));
    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toStartWith('https://wa.me/6281234567890?text=')
        ->and($response->headers->get('Location'))->toContain('Buku%20Statistik');

    $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 0]))
        ->assertRedirect(route('products.show', $product->slug))
        ->assertSessionHasErrors(['quantity']);
    $this->assertDatabaseHas('product_daily_metrics', [
        'product_id' => $product->id,
        'product_views' => 1,
        'whatsapp_clicks' => 1,
    ]);
    expect($product->fresh()->stock)->toBe(9);
});

it('does not count private product previews as public visits', function () {
    $product = metricPublishedProduct();
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.products.preview', $product))->assertOk();

    $this->assertDatabaseMissing('product_daily_metrics', ['product_id' => $product->id]);
});

it('does not record a WhatsApp click when no valid destination is configured', function () {
    $product = metricPublishedProduct();

    $this->get(route('products.whatsapp', ['product' => $product->slug, 'quantity' => 1]))
        ->assertNotFound();

    expect(ProductDailyMetric::query()
        ->where('product_id', $product->id)
        ->sum('whatsapp_clicks'))->toBe(0);
});

it('deduplicates each metric within its configured window without storing visitor identifiers', function () {
    $product = metricPublishedProduct();
    $recorder = app(ProductMetricRecorder::class);

    $recorder->recordOnce($product, 'product_view', 'private-session-id');
    $recorder->recordOnce($product, 'product_view', 'private-session-id');
    $recorder->recordOnce($product, 'whatsapp_click', 'private-session-id');
    $recorder->recordOnce($product, 'whatsapp_click', 'private-session-id');

    $metric = ProductDailyMetric::query()->firstOrFail();
    expect($metric->product_views)->toBe(1)
        ->and($metric->whatsapp_clicks)->toBe(1);
    expect(Schema::getColumnListing('product_daily_metrics'))
        ->not->toContain('session_id')
        ->not->toContain('ip_address');
});

it('shows aggregate statistics only to an authorized admin and labels WhatsApp clicks honestly', function () {
    $product = metricPublishedProduct();
    ProductDailyMetric::query()->create([
        'product_id' => $product->id,
        'metric_date' => today(),
        'product_views' => 12,
        'whatsapp_clicks' => 4,
    ]);

    $this->get(route('admin.statistics.products'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.statistics.products'))->assertForbidden();

    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get(route('admin.statistics.products', ['days' => 7]))
        ->assertOk()
        ->assertSee('Kunjungan produk')
        ->assertSee('12')
        ->assertSee('4')
        ->assertSee('bukan pesanan atau penjualan');
});

it('prunes daily metrics at the retention boundary and keeps newer aggregates', function () {
    $product = metricPublishedProduct();
    ProductDailyMetric::query()->create([
        'product_id' => $product->id,
        'metric_date' => today()->subDays(366),
        'product_views' => 3,
        'whatsapp_clicks' => 1,
    ]);
    ProductDailyMetric::query()->create([
        'product_id' => $product->id,
        'metric_date' => today()->subDays(365),
        'product_views' => 5,
        'whatsapp_clicks' => 2,
    ]);
    ProductDailyMetric::query()->create([
        'product_id' => $product->id,
        'metric_date' => today()->subDays(364),
        'product_views' => 7,
        'whatsapp_clicks' => 3,
    ]);

    $this->artisan('metrics:prune')->assertSuccessful();

    expect(ProductDailyMetric::query()->pluck('product_views')->all())->toBe([7]);
});
