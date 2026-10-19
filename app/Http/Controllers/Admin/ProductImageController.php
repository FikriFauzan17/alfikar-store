<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImagesRequest;
use App\Http\Requests\Admin\UpdateProductImageOrderRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AdminAuditLogger;
use App\Services\ProductImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ProductImageController extends Controller
{
    public function store(
        StoreProductImagesRequest $request,
        Product $product,
        ProductImageProcessor $imageProcessor,
        AdminAuditLogger $audit,
    ): RedirectResponse|JsonResponse {
        $storedImages = [];
        $imageAlt = $request->validated('image_alt') ?? [];

        try {
            foreach ($request->file('images') as $index => $upload) {
                $image = $imageProcessor->storeFor($product, $upload, $imageAlt[$index] ?? null);
                $storedImages[] = $image;
            }
            DB::transaction(function () use ($request, $audit, $storedImages): void {
                foreach ($storedImages as $image) {
                    $audit->record($request->user(), 'product.image.uploaded', $image);
                }
            });
        } catch (Throwable $exception) {
            foreach (array_reverse($storedImages) as $image) {
                try {
                    $imageProcessor->delete($image);
                } catch (Throwable) {
                    Log::warning('Failed to roll back an admin image batch.', [
                        'product_id' => $product->getKey(),
                        'image_id' => $image->getKey(),
                    ]);
                }
            }

            throw $exception;
        }

        if ($request->expectsJson()) {
            $imageMetadata = [];
            $uploadedFiles = $request->file('images');

            foreach ($storedImages as $index => $image) {
                $imageMetadata[] = [
                    'input_size' => $uploadedFiles[$index]->getSize(),
                    'width' => $image->width,
                    'height' => $image->height,
                    'stored_size' => $image->file_size,
                    'mime_type' => $image->mime_type,
                ];
            }

            return response()->json([
                'message' => 'Foto produk berhasil disimpan.',
                'images' => $imageMetadata,
            ], 201);
        }

        return redirect()->route('admin.products.edit', $product)->with('status', 'Foto produk berhasil disimpan.');
    }

    public function updateOrder(
        UpdateProductImageOrderRequest $request,
        Product $product,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $requestedOrder = $request->validated('image_order');

        DB::transaction(function () use ($request, $product, $requestedOrder, $audit): void {
            $lockedProduct = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $images = $lockedProduct->images()->orderBy('id')->lockForUpdate()->get();
            $actualIds = $images->modelKeys();
            $requestedIds = array_map('intval', array_keys($requestedOrder));
            sort($actualIds);
            sort($requestedIds);

            abort_unless($actualIds === $requestedIds, 404);

            $offset = $images->count() + (int) config('image-processing.max_images', 5);

            foreach ($images as $image) {
                $image->sort_order += $offset;
                $image->save();
            }

            foreach ($requestedOrder as $imageId => $sortOrder) {
                $image = $images->firstWhere('id', (int) $imageId);
                $image->sort_order = (int) $sortOrder;
                $image->save();
            }

            $audit->record($request->user(), 'product.images.reordered', $lockedProduct);
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'Urutan galeri diperbarui.');
    }

    public function destroy(
        Product $product,
        ProductImage $image,
        Request $request,
        ProductImageProcessor $imageProcessor,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        abort_unless((int) $image->product_id === (int) $product->getKey(), 404);

        $imageProcessor->delete($image);
        $audit->record($request->user(), 'product.image.deleted', $image);
        $this->normalizeOrder($product);

        return redirect()->route('admin.products.edit', $product)->with('status', 'Foto produk berhasil dihapus.');
    }

    public function confirmDelete(Product $product, ProductImage $image): View
    {
        abort_unless((int) $image->product_id === (int) $product->getKey(), 404);

        return view('admin.products.images.confirm-delete', compact('product', 'image'));
    }

    private function normalizeOrder(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $lockedProduct = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $images = $lockedProduct->images()->orderBy('sort_order')->lockForUpdate()->get();
            $offset = $images->count() + (int) config('image-processing.max_images', 5);

            foreach ($images as $image) {
                $image->sort_order += $offset;
                $image->save();
            }

            foreach ($images->values() as $sortOrder => $image) {
                $image->sort_order = $sortOrder;
                $image->save();
            }
        });
    }
}
