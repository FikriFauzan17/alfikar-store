<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use App\Services\AdminAuditLogger;
use App\Services\ProductImageProcessor;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.categories.index', ['categories' => $categories]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        SaveCategoryRequest $request,
        AdminAuditLogger $audit,
        ProductImageProcessor $images,
    ): RedirectResponse
    {
        $attributes = $request->safe()->only(['name', 'slug', 'description', 'sort_order', 'is_published']);
        $imagePath = null;
        $categoryId = null;

        try {
            $category = DB::transaction(function () use ($request, $audit, $images, $attributes, &$imagePath, &$categoryId): Category {
                $category = Category::query()->create($attributes);
                $categoryId = (int) $category->getKey();

                if ($request->hasFile('image')) {
                    $imagePath = $images->storeCategoryImage($categoryId, $request->file('image'));
                    $category->image_path = $imagePath;
                    $category->save();
                }

                $audit->record($request->user(), 'category.created', $category);

                return $category;
            });
        } catch (\Throwable $exception) {
            if ($imagePath !== null && $categoryId !== null) {
                $images->deleteCategoryImage($categoryId, $imagePath);
            }

            throw $exception;
        }

        return redirect()->route('admin.categories.index')->with('status', 'Kategori berhasil dibuat.');
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
    public function edit(Category $category): View
    {
        return view('admin.categories.edit', ['category' => $category]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        SaveCategoryRequest $request,
        Category $category,
        AdminAuditLogger $audit,
        ProductImageProcessor $images,
    ): RedirectResponse {
        $attributes = $request->safe()->only(['name', 'slug', 'description', 'sort_order', 'is_published']);
        $previousImagePath = $category->image_path;
        $newImagePath = null;

        try {
            DB::transaction(function () use ($request, $category, $audit, $images, $attributes, &$newImagePath): void {
                $lockedCategory = Category::query()->whereKey($category->getKey())->lockForUpdate()->firstOrFail();
                $lockedCategory->fill($attributes);

                if ($request->boolean('remove_image')) {
                    $lockedCategory->image_path = null;
                }

                if ($request->hasFile('image')) {
                    $newImagePath = $images->storeCategoryImage(
                        (int) $lockedCategory->getKey(),
                        $request->file('image'),
                    );
                    $lockedCategory->image_path = $newImagePath;
                }

                $lockedCategory->save();
                $audit->record($request->user(), 'category.updated', $lockedCategory);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                $images->deleteCategoryImage((int) $category->getKey(), $newImagePath);
            }

            throw $exception;
        }

        if (($newImagePath !== null || $request->boolean('remove_image')) && $previousImagePath !== null) {
            $images->deleteCategoryImage((int) $category->getKey(), $previousImagePath);
        }

        return redirect()->route('admin.categories.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function confirmDelete(Category $category): View
    {
        return view('admin.categories.confirm-delete', ['category' => $category]);
    }

    public function destroy(
        Category $category,
        Request $request,
        AdminAuditLogger $audit,
        ProductImageProcessor $images,
    ): RedirectResponse
    {
        $imagePath = $category->image_path;
        $categoryId = (int) $category->getKey();

        try {
            DB::transaction(function () use ($request, $category, $audit): void {
                $category->delete();
                $audit->record($request->user(), 'category.deleted', $category);
            });
        } catch (QueryException) {
            return back()->withErrors(['category' => 'Kategori masih digunakan produk dan tidak dapat dihapus.']);
        }

        $images->deleteCategoryImage($categoryId, $imagePath);

        return redirect()->route('admin.categories.index')->with('status', 'Kategori berhasil dihapus.');
    }
}
