<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Services\AdminAuditLogger;
use App\Services\ProductImageProcessor;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $products = Product::query()
            ->with([
                'category:id,name',
                'images' => fn (HasMany $query) => $query->select([
                    'id',
                    'product_id',
                    'disk',
                    'path',
                    'alt_text',
                    'variants',
                    'width',
                    'height',
                    'file_size',
                    'mime_type',
                    'sort_order',
                ]),
            ])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('admin.products.index', ['products' => $products]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.products.create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveProductRequest $request, AdminAuditLogger $audit): RedirectResponse
    {
        $product = DB::transaction(function () use ($request, $audit): Product {
            $product = Product::query()->create($request->productAttributes());
            $audit->record($request->user(), 'product.created', $product);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'Produk berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        $product->load([
            'category:id,name',
            'images' => fn (HasMany $query) => $query->orderBy('sort_order'),
        ]);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveProductRequest $request, Product $product, AdminAuditLogger $audit): RedirectResponse
    {
        $attributes = $request->productAttributes();

        DB::transaction(function () use ($request, $product, $audit, $attributes): void {
            $lockedProduct = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $previousSlug = $lockedProduct->slug;
            ProductSlugRedirect::query()
                ->where('product_id', $lockedProduct->getKey())
                ->where('slug', $attributes['slug'])
                ->delete();
            $lockedProduct->update($attributes);

            if ($previousSlug !== $lockedProduct->slug) {
                ProductSlugRedirect::query()->updateOrCreate(
                    ['slug' => $previousSlug],
                    ['product_id' => $lockedProduct->getKey()],
                );
            }

            $audit->record($request->user(), 'product.updated', $lockedProduct);
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'Produk berhasil diperbarui.');
    }

    public function confirmDelete(Product $product): View
    {
        return view('admin.products.confirm-delete', [
            'product' => $product->loadCount('images'),
        ]);
    }

    public function destroy(
        Request $request,
        Product $product,
        ProductImageProcessor $imageProcessor,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        try {
            DB::transaction(function () use ($request, $product, $imageProcessor, $audit): void {
                $lockedProduct = Product::query()
                    ->with('images')
                    ->whereKey($product->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                foreach ($lockedProduct->images as $image) {
                    $imageProcessor->delete($image);
                }

                $lockedProduct->delete();
                $audit->record($request->user(), 'product.deleted', $lockedProduct);
            });
        } catch (QueryException|\RuntimeException) {
            return back()->withErrors(['product' => 'Produk tidak dapat dihapus karena masih digunakan.']);
        }

        return redirect()->route('admin.products.index')->with('status', 'Produk dan foto terkait berhasil dihapus.');
    }
}
