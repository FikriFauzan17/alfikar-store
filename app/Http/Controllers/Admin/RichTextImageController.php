<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRichTextImageRequest;
use App\Services\ProductImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RichTextImageController extends Controller
{
    public function store(
        StoreRichTextImageRequest $request,
        ProductImageProcessor $images,
    ): JsonResponse {
        $image = $request->file('image');
        abort_unless($image instanceof UploadedFile, 422);
        $path = $images->storeContentImage('rich-content/1', $image);

        return response()->json([
            'url' => Storage::disk((string) config('image-processing.disk', 'public'))->url($path),
        ], 201);
    }
}
