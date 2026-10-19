<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\AdminAuditLogger;
use App\Services\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function create(Product $product): View
    {
        return view('admin.stock-adjustments.create', [
            'product' => $product,
            'submissionToken' => (string) Str::uuid(),
        ]);
    }

    public function store(
        StoreStockAdjustmentRequest $request,
        Product $product,
        StockAdjustmentService $adjustments,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $adjustment = $adjustments->record(
            $product,
            $request->user(),
            $request->safe()->only(['direction', 'reason', 'quantity', 'notes', 'submission_token']),
            $audit,
        );

        return redirect()
            ->route('admin.products.stock-adjustments.index', $product)
            ->with('status', $adjustment === null
                ? 'Pengiriman duplikat diabaikan; stok tidak berubah.'
                : 'Penyesuaian stok berhasil dicatat.');
    }

    public function index(Request $request, Product $product): View
    {
        $filters = Validator::make($request->query(), [
            'direction' => ['nullable', 'in:increase,decrease'],
            'reason' => ['nullable', 'in:'.implode(',', StockAdjustment::REASONS)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ])->validate();

        $adjustments = StockAdjustment::query()
            ->where('product_id', $product->getKey())
            ->when($filters['direction'] ?? null, fn ($query, string $direction) => $query->where('direction', $direction))
            ->when($filters['reason'] ?? null, fn ($query, string $reason) => $query->where('reason', $reason))
            ->when($filters['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.stock-adjustments.index', [
            'product' => $product,
            'adjustments' => $adjustments,
            'filters' => $filters,
        ]);
    }
}
