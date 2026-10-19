<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDailyMetric;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductStatisticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90,365'],
        ]);
        $days = (int) ($filters['days'] ?? 30);
        $startDate = now()->subDays($days - 1)->toDateString();
        $endDate = now()->toDateString();
        $rangeQuery = fn (): Builder => ProductDailyMetric::query()
            ->whereBetween('metric_date', [$startDate, $endDate]);
        $metrics = $rangeQuery()
            ->selectRaw('product_id, SUM(product_views) AS product_views, SUM(whatsapp_clicks) AS whatsapp_clicks')
            ->groupBy('product_id')
            ->with('product:id,title,slug')
            ->orderByDesc('whatsapp_clicks')
            ->orderByDesc('product_views')
            ->orderBy('product_id')
            ->paginate(20)
            ->appends(['days' => $days]);

        return view('admin.statistics.products', [
            'metrics' => $metrics,
            'days' => $days,
            'startDate' => $startDate,
            'deduplicateMinutes' => max(1, (int) config('product-metrics.deduplicate_minutes', 30)),
            'retentionDays' => max(1, (int) config('product-metrics.retention_days', 365)),
            'totals' => [
                'productViews' => $rangeQuery()->sum('product_views'),
                'whatsappClicks' => $rangeQuery()->sum('whatsapp_clicks'),
            ],
        ]);
    }
}
