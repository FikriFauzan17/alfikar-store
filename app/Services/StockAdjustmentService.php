<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    /**
     * Return null when the same admin already submitted this product adjustment token.
     *
     * @param  array{direction: string, reason: string, quantity: int|string, notes?: string|null, submission_token: string}  $attributes
     */
    public function record(
        Product $product,
        User $actor,
        array $attributes,
        AdminAuditLogger $audit,
    ): ?StockAdjustment
    {
        try {
            return DB::transaction(function () use ($product, $actor, $attributes, $audit): ?StockAdjustment {
                $lockedProduct = Product::query()
                    ->whereKey($product->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $existingAdjustment = StockAdjustment::query()
                    ->where('submission_token', $attributes['submission_token'])
                    ->first();

                if ($existingAdjustment !== null) {
                    if (
                        (int) $existingAdjustment->product_id === (int) $lockedProduct->getKey()
                        && (int) $existingAdjustment->actor_id === (int) $actor->getAuthIdentifier()
                    ) {
                        return null;
                    }

                    throw ValidationException::withMessages([
                        'submission_token' => 'Token pengiriman sudah digunakan. Muat ulang formulir.',
                    ]);
                }

                $stockBefore = (int) $lockedProduct->stock;
                $quantity = (int) $attributes['quantity'];
                $stockAfter = $attributes['direction'] === 'increase'
                    ? $stockBefore + $quantity
                    : $stockBefore - $quantity;

                if ($stockAfter < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah pengurangan melebihi stok yang tersedia.',
                    ]);
                }

                if ($stockAfter > 4294967295) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah penambahan melebihi batas stok produk.',
                    ]);
                }

                $updatedRows = Product::query()
                    ->whereKey($lockedProduct->getKey())
                    ->where('stock', $stockBefore)
                    ->update([
                        'stock' => $stockAfter,
                        'updated_at' => now(),
                    ]);

                if ($updatedRows !== 1) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Stok berubah bersamaan. Periksa stok terbaru lalu coba lagi.',
                    ]);
                }

                $lockedProduct->setAttribute('stock', $stockAfter);
                $adjustment = StockAdjustment::query()->create([
                    'product_id' => $lockedProduct->getKey(),
                    'product_code' => $lockedProduct->code,
                    'product_title' => $lockedProduct->title,
                    'actor_id' => $actor->getAuthIdentifier(),
                    'actor_name' => $actor->name,
                    'direction' => $attributes['direction'],
                    'reason' => $attributes['reason'],
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' => $attributes['notes'] ?? null,
                    'submission_token' => $attributes['submission_token'],
                ]);

                $audit->record($actor, 'product.stock-adjusted', $adjustment);

                return $adjustment;
            });
        } catch (QueryException $exception) {
            $existingAdjustment = StockAdjustment::query()
                ->where('submission_token', $attributes['submission_token'])
                ->first();

            if ($existingAdjustment === null) {
                throw $exception;
            }

            if (
                (int) $existingAdjustment->product_id === (int) $product->getKey()
                && (int) $existingAdjustment->actor_id === (int) $actor->getAuthIdentifier()
            ) {
                return null;
            }

            throw ValidationException::withMessages([
                'submission_token' => 'Token pengiriman sudah digunakan. Muat ulang formulir.',
            ]);
        }
    }
}
