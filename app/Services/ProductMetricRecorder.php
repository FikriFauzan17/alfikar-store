<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductMetricRecorder
{
    public function recordOnce(Product $product, string $metric, string $sessionId): void
    {
        $column = match ($metric) {
            'product_view' => 'product_views',
            'whatsapp_click' => 'whatsapp_clicks',
            default => throw new \InvalidArgumentException('Unsupported product metric.'),
        };
        $deduplicateMinutes = max(1, (int) config('product-metrics.deduplicate_minutes', 30));
        $deduplicationKey = 'product-metric-once:'.hash_hmac(
            'sha256',
            $product->getKey().'|'.$metric.'|'.$sessionId.'|'.now()->toDateString(),
            (string) config('app.key'),
        );

        if (! Cache::add($deduplicationKey, true, now()->addMinutes($deduplicateMinutes))) {
            return;
        }

        try {
            DB::transaction(function () use ($product, $column): void {
                $date = now()->toDateString();

                DB::table('product_daily_metrics')->insertOrIgnore([
                    'product_id' => $product->getKey(),
                    'metric_date' => $date,
                    'product_views' => 0,
                    'whatsapp_clicks' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('product_daily_metrics')
                    ->where('product_id', $product->getKey())
                    ->where('metric_date', $date)
                    ->increment($column, 1, ['updated_at' => now()]);
            });
        } catch (Throwable $exception) {
            Cache::forget($deduplicationKey);

            throw $exception;
        }
    }

    public function pruneExpired(): int
    {
        $retentionDays = max(1, (int) config('product-metrics.retention_days', 365));

        return DB::table('product_daily_metrics')
            ->whereDate('metric_date', '<=', now()->subDays($retentionDays)->toDateString())
            ->delete();
    }
}
