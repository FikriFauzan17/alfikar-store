<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBannerRequest;
use App\Models\Banner;
use App\Services\AdminAuditLogger;
use App\Services\ProductImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::query()->orderBy('sort_order')->orderByDesc('updated_at')->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        SaveBannerRequest $request,
        ProductImageProcessor $images,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $imagePath = null;
        $bannerId = null;

        try {
            $banner = DB::transaction(function () use ($request, $images, $audit, &$imagePath, &$bannerId): Banner {
                $banner = Banner::query()->create($request->safe()->only([
                    'eyebrow', 'title', 'description', 'image_alt', 'link_url', 'link_label',
                    'starts_at', 'ends_at', 'sort_order', 'is_active',
                ]));
                $bannerId = (int) $banner->getKey();

                if ($request->hasFile('image')) {
                    $imagePath = $images->storeContentImage('banners/'.$banner->getKey(), $request->file('image'));
                    $banner->image_path = $imagePath;
                    $banner->save();
                }

                $audit->record($request->user(), 'banner.created', $banner);

                return $banner;
            });
        } catch (\Throwable $exception) {
            if ($imagePath !== null && $bannerId !== null) {
                $images->deleteContentImage('banners/'.$bannerId, $imagePath);
            }

            throw $exception;
        }

        return redirect()->route('admin.banners.edit', $banner)->with('status', 'Banner berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Banner $banner): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', ['banner' => $banner]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        SaveBannerRequest $request,
        Banner $banner,
        ProductImageProcessor $images,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $previousImagePath = $banner->image_path;
        $newImagePath = null;

        try {
            DB::transaction(function () use ($request, $banner, $images, $audit, &$newImagePath): void {
                $lockedBanner = Banner::query()->whereKey($banner->getKey())->lockForUpdate()->firstOrFail();
                $lockedBanner->fill($request->safe()->only([
                    'eyebrow', 'title', 'description', 'image_alt', 'link_url', 'link_label',
                    'starts_at', 'ends_at', 'sort_order', 'is_active',
                ]));

                if ($request->boolean('remove_image')) {
                    $lockedBanner->image_path = null;
                    $lockedBanner->image_alt = null;
                }

                if ($request->hasFile('image')) {
                    $newImagePath = $images->storeContentImage('banners/'.$lockedBanner->getKey(), $request->file('image'));
                    $lockedBanner->image_path = $newImagePath;
                    $lockedBanner->image_alt = $request->validated('image_alt');
                }

                $lockedBanner->save();
                $audit->record($request->user(), 'banner.updated', $lockedBanner);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                $images->deleteContentImage('banners/'.$banner->getKey(), $newImagePath);
            }

            throw $exception;
        }

        if (($newImagePath !== null || $request->boolean('remove_image')) && $previousImagePath !== null) {
            $images->deleteContentImage('banners/'.$banner->getKey(), $previousImagePath);
        }

        return redirect()->route('admin.banners.edit', $banner)->with('status', 'Banner berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function confirmDelete(Banner $banner): View
    {
        return view('admin.banners.confirm-delete', ['banner' => $banner]);
    }

    public function destroy(
        Banner $banner,
        Request $request,
        ProductImageProcessor $images,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $imagePath = $banner->image_path;
        $bannerId = $banner->getKey();

        DB::transaction(function () use ($banner, $request, $audit): void {
            $banner->delete();
            $audit->record($request->user(), 'banner.deleted', $banner);
        });

        if ($imagePath !== null) {
            $images->deleteContentImage('banners/'.$bannerId, $imagePath);
        }

        return redirect()->route('admin.banners.index')->with('status', 'Banner berhasil dihapus.');
    }
}
