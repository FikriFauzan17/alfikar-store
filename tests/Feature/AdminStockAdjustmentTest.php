<?php

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\StockAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function adminStockAdjustmentPayload(array $overrides = []): array
{
    return array_replace([
        'direction' => 'decrease',
        'reason' => 'whatsapp_sale',
        'quantity' => 2,
        'notes' => 'Transaksi WhatsApp dikonfirmasi.',
        'submission_token' => (string) Str::uuid(),
    ], $overrides);
}

it('records stock changes with audit snapshots and refuses reductions beyond available stock', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Stok']);
    $product = Product::factory()->create(['stock' => 5]);

    $this->get(route('admin.products.stock-adjustments.create', $product))
        ->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())
        ->post(route('admin.products.stock-adjustments.store', $product), adminStockAdjustmentPayload())
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.products.stock-adjustments.store', $product), adminStockAdjustmentPayload())
        ->assertRedirect(route('admin.products.stock-adjustments.index', $product));

    expect($product->fresh()->stock)->toBe(3);
    $adjustment = StockAdjustment::query()->sole();
    expect($adjustment->direction)->toBe('decrease')
        ->and($adjustment->reason)->toBe('whatsapp_sale')
        ->and($adjustment->quantity)->toBe(2)
        ->and($adjustment->stock_before)->toBe(5)
        ->and($adjustment->stock_after)->toBe(3)
        ->and($adjustment->actor_name)->toBe('Admin Stok');
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'product.stock-adjusted',
        'subject_type' => StockAdjustment::class,
        'subject_id' => $adjustment->id,
    ]);

    $this->from(route('admin.products.stock-adjustments.create', $product))
        ->post(route('admin.products.stock-adjustments.store', $product), adminStockAdjustmentPayload([
            'quantity' => 4,
        ]))
        ->assertRedirect(route('admin.products.stock-adjustments.create', $product))
        ->assertSessionHasErrors('quantity');

    expect($product->fresh()->stock)->toBe(3)
        ->and(StockAdjustment::query()->count())->toBe(1);

    $this->post(route('admin.products.stock-adjustments.store', $product), adminStockAdjustmentPayload([
        'direction' => 'increase',
        'reason' => 'goods_received',
        'quantity' => 7,
    ]))->assertRedirect(route('admin.products.stock-adjustments.index', $product));

    expect($product->fresh()->stock)->toBe(10)
        ->and(StockAdjustment::query()->orderBy('id')->pluck('stock_before')->all())->toBe([5, 3])
        ->and(StockAdjustment::query()->orderBy('id')->pluck('stock_after')->all())->toBe([3, 10]);
});

it('ignores a repeated submission and validates the reason direction and token ownership', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create(['stock' => 8]);
    $payload = adminStockAdjustmentPayload();
    $this->actingAs($admin);

    $this->post(route('admin.products.stock-adjustments.store', $product), $payload)
        ->assertRedirect(route('admin.products.stock-adjustments.index', $product));
    $this->post(route('admin.products.stock-adjustments.store', $product), $payload)
        ->assertRedirect(route('admin.products.stock-adjustments.index', $product))
        ->assertSessionHas('status', 'Pengiriman duplikat diabaikan; stok tidak berubah.');

    $this->from(route('admin.products.stock-adjustments.create', $product))
        ->post(route('admin.products.stock-adjustments.store', $product), adminStockAdjustmentPayload([
            'reason' => 'goods_received',
            'direction' => 'decrease',
        ]))
        ->assertRedirect(route('admin.products.stock-adjustments.create', $product))
        ->assertSessionHasErrors('direction');

    $otherAdmin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($otherAdmin)
        ->from(route('admin.products.stock-adjustments.create', $product))
        ->post(route('admin.products.stock-adjustments.store', $product), $payload)
        ->assertRedirect(route('admin.products.stock-adjustments.create', $product))
        ->assertSessionHasErrors('submission_token');

    expect($product->fresh()->stock)->toBe(6)
        ->and(StockAdjustment::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('event', 'product.stock-adjusted')->count())->toBe(1);
});

it('uses the current locked stock and prevents ordinary product edits from overwriting adjustments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create(['stock' => 12]);
    $staleProduct = new Product;
    $staleProduct->setRawAttributes($product->getAttributes());
    $service = app(StockAdjustmentService::class);
    $audit = app(AdminAuditLogger::class);

    $service->record($product, $admin, adminStockAdjustmentPayload(), $audit);
    $service->record($staleProduct, $admin, adminStockAdjustmentPayload(), $audit);

    expect($product->fresh()->stock)->toBe(8)
        ->and(StockAdjustment::query()->orderBy('id')->pluck('stock_before')->all())->toBe([12, 10])
        ->and(StockAdjustment::query()->orderBy('id')->pluck('stock_after')->all())->toBe([10, 8]);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'code' => $product->code,
            'slug' => $product->slug,
            'title' => $product->title,
            'author' => $product->author,
            'publisher' => $product->publisher,
            'isbn' => $product->isbn,
            'description' => $product->description,
            'specifications_json' => '',
            'stock' => 99,
            'unit_price' => $product->unit_price,
            'wholesale_price' => null,
            'wholesale_minimum' => null,
            'is_published' => '0',
            'is_featured' => '0',
            'is_bestseller' => '0',
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ])
        ->assertRedirect(route('admin.products.edit', $product));

    expect($product->fresh()->stock)->toBe(8);
});

it('lists immutable stock history with filters and pagination while escaping notes', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create(['stock' => 30]);
    $otherProduct = Product::factory()->create();
    $this->actingAs($admin);

    for ($index = 0; $index < 22; $index++) {
        $this->post(route('admin.products.stock-adjustments.store', $product), adminStockAdjustmentPayload([
            'direction' => 'increase',
            'reason' => 'correction',
            'quantity' => 1,
            'notes' => 'Koreksi '.$index,
        ]))->assertRedirect(route('admin.products.stock-adjustments.index', $product));
    }

    $this->post(route('admin.products.stock-adjustments.store', $product), adminStockAdjustmentPayload([
        'direction' => 'increase',
        'reason' => 'correction',
        'quantity' => 1,
        'notes' => '<script>alert(1)</script>',
    ]))->assertRedirect(route('admin.products.stock-adjustments.index', $product));
    $this->post(route('admin.products.stock-adjustments.store', $otherProduct), adminStockAdjustmentPayload([
        'direction' => 'increase',
        'reason' => 'goods_received',
        'quantity' => 1,
    ]))->assertRedirect(route('admin.products.stock-adjustments.index', $otherProduct));

    $this->get(route('admin.products.stock-adjustments.index', $product))
        ->assertOk()
        ->assertSee('Riwayat stok')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Koreksi 21')
        ->assertDontSee('Koreksi 0');
    $this->get(route('admin.products.stock-adjustments.index', $product).'?reason=correction&direction=increase&page=2')
        ->assertOk()
        ->assertSee('Koreksi 0')
        ->assertDontSee($otherProduct->code);
    $this->get(route('admin.products.stock-adjustments.index', $product).'?reason=unknown')
        ->assertSessionHasErrors('reason');
    $this->delete(route('admin.products.stock-adjustments.index', $product))
        ->assertMethodNotAllowed();

    expect(StockAdjustment::query()->where('product_id', $product->id)->count())->toBe(23);
});
