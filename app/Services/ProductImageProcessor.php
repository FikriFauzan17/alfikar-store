<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Throwable;

final class ProductImageProcessor
{
    private const SITE_LOGO_DIRECTORY = 'site/1';

    public function storeCategoryImage(int $categoryId, UploadedFile $upload): string
    {
        return $this->storeContentImage('categories/'.$categoryId, $upload);
    }

    public function deleteCategoryImage(int $categoryId, ?string $path): void
    {
        $this->deleteContentImage('categories/'.$categoryId, $path);
    }

    public function storeLogoImage(UploadedFile $upload): string
    {
        return $this->storeContentImage(self::SITE_LOGO_DIRECTORY, $upload);
    }

    public function deleteLogoImage(?string $path): void
    {
        $this->deleteContentImage(self::SITE_LOGO_DIRECTORY, $path);
    }

    public function storeContentImage(string $directory, UploadedFile $upload): string
    {
        if (! preg_match('/\A(?:(?:articles|banners|categories)\/[1-9]\d*|site\/1)\z/', $directory)) {
            throw new LogicException('The content image directory is invalid.');
        }

        $source = $this->inspectUpload($upload);
        $this->assertMemoryBudget($source['pixels']);
        $this->assertImageBackend($source['mime']);
        $image = $this->decode($source['path'], $source['mime']);
        $temporaryVariants = [];

        try {
            $image = $this->applyOrientation($image, $source['path'], $source['mime']);
            $temporaryVariants = $this->createVariants($image);
        } finally {
            imagedestroy($image);
        }

        $primaryWidth = max(array_keys($temporaryVariants));
        $path = $directory.'/'.Str::uuid().'.webp';
        $diskName = (string) config('image-processing.disk', 'public');

        try {
            $stream = @fopen($temporaryVariants[$primaryWidth]['temporary_path'], 'rb');

            if (! is_resource($stream)) {
                throw new RuntimeException('Unable to read a processed content image.');
            }

            try {
                if (! Storage::disk($diskName)->put($path, $stream)) {
                    throw new RuntimeException('Unable to store a processed content image.');
                }
            } finally {
                fclose($stream);
            }

            return $path;
        } catch (Throwable $exception) {
            $this->cleanupStoredFiles($diskName, [$path], (int) basename($directory));

            throw $exception;
        } finally {
            $this->cleanupTemporaryFiles($temporaryVariants);
        }
    }

    public function deleteContentImage(string $directory, ?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (
            ! preg_match('/\A(?:(?:articles|banners|categories)\/[1-9]\d*|site\/1)\z/', $directory)
            || ! preg_match('/\A'.preg_quote($directory, '/').'\/[0-9a-f-]{36}\.webp\z/i', $path)
        ) {
            throw new LogicException('The content image path is outside its managed resource directory.');
        }

        $disk = Storage::disk((string) config('image-processing.disk', 'public'));

        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Unable to remove the content image.');
        }
    }

    public function storeFor(Product $product, UploadedFile $upload, ?string $altText = null): ProductImage
    {
        if (! $product->exists || $product->getKey() === null) {
            throw new LogicException('Images can only be attached to a saved product.');
        }

        $maxImages = (int) config('image-processing.max_images', 5);

        if ($maxImages < 1) {
            throw new RuntimeException('The product image limit is not configured.');
        }

        $this->assertImageCapacity($product, $maxImages);

        $altText = trim((string) $altText);
        $altText = $altText === '' ? $product->title : $altText;

        if ($this->textLength($altText) > 255) {
            $this->reject('Teks alternatif maksimal 255 karakter.');
        }

        $source = $this->inspectUpload($upload);
        $this->assertMemoryBudget($source['pixels']);
        $this->assertImageBackend($source['mime']);

        $image = $this->decode($source['path'], $source['mime']);
        $temporaryVariants = [];

        try {
            $image = $this->applyOrientation($image, $source['path'], $source['mime']);
            $temporaryVariants = $this->createVariants($image);
        } finally {
            imagedestroy($image);
        }

        $diskName = (string) config('image-processing.disk', 'public');
        $disk = Storage::disk($diskName);
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($product, $maxImages, $altText, $temporaryVariants, $diskName, $disk, &$storedPaths): ProductImage {
                $lockedProduct = Product::query()
                    ->whereKey($product->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $existingOrders = $lockedProduct->images()->pluck('sort_order')
                    ->map(fn ($sortOrder): int => (int) $sortOrder)
                    ->all();

                if (count($existingOrders) >= $maxImages) {
                    $this->reject('Produk sudah mencapai batas jumlah foto.');
                }

                $sortOrder = $this->availableSortOrder($existingOrders, $maxImages);
                $directory = 'products/'.$lockedProduct->getKey().'/'.Str::uuid();
                $variantPaths = [];
                $totalBytes = 0;

                foreach ($temporaryVariants as $width => $variant) {
                    $relativePath = $directory.'/'.$width.'.webp';
                    $storedPaths[] = $relativePath;
                    $stream = @fopen($variant['temporary_path'], 'rb');

                    if (! is_resource($stream)) {
                        throw new RuntimeException('Unable to read a processed image variant.');
                    }

                    try {
                        $stored = $disk->put($relativePath, $stream);
                    } finally {
                        fclose($stream);
                    }

                    if (! $stored) {
                        throw new RuntimeException('Unable to store a processed image variant.');
                    }

                    $variantPaths[$width] = $relativePath;
                    $totalBytes += $variant['file_size'];
                }

                $primaryWidth = max(array_keys($variantPaths));
                $primary = $temporaryVariants[$primaryWidth];

                return $lockedProduct->images()->create([
                    'disk' => $diskName,
                    'path' => $variantPaths[$primaryWidth],
                    'alt_text' => $altText,
                    'variants' => $variantPaths,
                    'width' => $primary['width'],
                    'height' => $primary['height'],
                    'file_size' => $totalBytes,
                    'mime_type' => 'image/webp',
                    'sort_order' => $sortOrder,
                ]);
            });
        } catch (Throwable $exception) {
            $this->cleanupStoredFiles($diskName, $storedPaths, (int) $product->getKey());

            throw $exception;
        } finally {
            $this->cleanupTemporaryFiles($temporaryVariants);
        }
    }

    public function delete(ProductImage $image): void
    {
        if (! $image->exists) {
            throw new LogicException('Only saved product images can be deleted.');
        }

        $diskName = (string) config('image-processing.disk', 'public');

        if ($image->disk !== $diskName) {
            throw new LogicException('The image is not stored on the configured product image disk.');
        }

        $paths = $this->managedVariantPaths($image);
        $disk = Storage::disk($diskName);

        try {
            foreach ($paths as $path) {
                if ($disk->exists($path) && ! $disk->delete($path)) {
                    throw new RuntimeException('Storage did not remove an image variant.');
                }
            }
        } catch (Throwable) {
            throw new RuntimeException('Unable to remove product image variants.');
        }

        if (! $image->delete()) {
            throw new RuntimeException('Unable to remove the product image record.');
        }
    }

    /**
     * @return array{path: string, mime: string, width: int, height: int, pixels: int}
     */
    private function inspectUpload(UploadedFile $upload): array
    {
        if (! $upload->isValid()) {
            $this->reject('File gambar gagal diunggah.');
        }

        $path = $upload->getRealPath();
        $size = $upload->getSize();
        $maxBytes = (int) config('image-processing.max_upload_bytes', 5 * 1024 * 1024);

        if (! is_string($path) || ! is_file($path) || ! is_int($size) || $size < 1 || $size > $maxBytes) {
            $this->reject('Ukuran gambar harus lebih dari 0 dan tidak melebihi batas unggah.');
        }

        $imageInfo = @getimagesize($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $allowedTypes = [
            'image/jpeg' => IMAGETYPE_JPEG,
            'image/png' => IMAGETYPE_PNG,
            'image/webp' => IMAGETYPE_WEBP,
        ];

        if (
            ! is_array($imageInfo)
            || ! is_string($mime)
            || ! isset($allowedTypes[$mime])
            || ($imageInfo['mime'] ?? null) !== $mime
            || ($imageInfo[2] ?? null) !== $allowedTypes[$mime]
        ) {
            $this->reject('File harus berupa JPEG, PNG, atau WebP yang valid.');
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $pixels = $width * $height;
        $maxDimension = (int) config('image-processing.max_dimension', 12_000);
        $maxPixels = (int) config('image-processing.max_pixels', 20_000_000);

        if ($width < 1 || $height < 1 || $width > $maxDimension || $height > $maxDimension || $pixels > $maxPixels) {
            $this->reject('Dimensi gambar melebihi batas yang diizinkan.');
        }

        return [
            'path' => $path,
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
            'pixels' => $pixels,
        ];
    }

    private function assertMemoryBudget(int $pixels): void
    {
        $bytesPerPixel = max(4, (int) config('image-processing.estimated_bytes_per_pixel', 8));
        $reserveBytes = max(0, (int) config('image-processing.memory_reserve_bytes', 16 * 1024 * 1024));
        $maximumBytes = max(1, (int) config('image-processing.max_estimated_memory_bytes', 192 * 1024 * 1024));
        $estimatedBytes = ($pixels * $bytesPerPixel) + $reserveBytes;

        if ($estimatedBytes > $maximumBytes) {
            $this->reject('Gambar membutuhkan resource decoder melebihi batas server.');
        }

        $memoryLimit = $this->memoryLimitBytes();

        if ($memoryLimit !== PHP_INT_MAX && $estimatedBytes > max(0, $memoryLimit - memory_get_usage(true))) {
            $this->reject('Gambar membutuhkan lebih banyak memori daripada yang tersedia.');
        }
    }

    private function memoryLimitBytes(): int
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return PHP_INT_MAX;
        }

        if (! preg_match('/\A(\d+(?:\.\d+)?)\s*([KMG]?)\z/i', $limit, $matches)) {
            return PHP_INT_MAX;
        }

        $multiplier = match (strtoupper($matches[2])) {
            'K' => 1024,
            'M' => 1024 ** 2,
            'G' => 1024 ** 3,
            default => 1,
        };

        return (int) ((float) $matches[1] * $multiplier);
    }

    private function assertImageBackend(string $mime): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new RuntimeException('Image uploads require PHP GD with WebP support.');
        }

        if ($mime === 'image/jpeg' && ! function_exists('exif_read_data')) {
            throw new RuntimeException('JPEG uploads require the PHP EXIF extension for orientation correction.');
        }
    }

    private function decode(string $path, string $mime): \GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
        };

        if (! $image instanceof \GdImage) {
            $this->reject('Isi file gambar tidak dapat didekode.');
        }

        return $image;
    }

    private function applyOrientation(\GdImage $image, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg') {
            return $image;
        }

        $metadata = @exif_read_data($path, 'IFD0', true, false);
        $orientation = (int) ($metadata['IFD0']['Orientation'] ?? $metadata['Orientation'] ?? 1);

        return match ($orientation) {
            2 => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, IMG_FLIP_VERTICAL),
            5 => $this->rotate($this->flip($image, IMG_FLIP_HORIZONTAL), -90),
            6 => $this->rotate($image, -90),
            7 => $this->rotate($this->flip($image, IMG_FLIP_HORIZONTAL), 90),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function flip(\GdImage $image, int $mode): \GdImage
    {
        if (! imageflip($image, $mode)) {
            throw new RuntimeException('Unable to correct image orientation.');
        }

        return $image;
    }

    private function rotate(\GdImage $image, int $angle): \GdImage
    {
        $background = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $rotated = @imagerotate($image, $angle, $background);

        if (! $rotated instanceof \GdImage) {
            throw new RuntimeException('Unable to rotate the uploaded image.');
        }

        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);
        imagedestroy($image);

        return $rotated;
    }

    /**
     * @return array<int, array{temporary_path: string, width: int, height: int, file_size: int}>
     */
    private function createVariants(\GdImage $source): array
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $configuredVariants = (array) config('image-processing.variants', []);
        $targets = [];

        foreach ($configuredVariants as $variant) {
            $maxWidth = (int) ($variant['max_width'] ?? 0);
            $targetBytes = (int) ($variant['target_bytes'] ?? 0);

            if ($maxWidth < 1 || $maxWidth > 1200 || $targetBytes < 1) {
                throw new RuntimeException('Image variant configuration is invalid.');
            }

            $width = min($sourceWidth, $maxWidth);
            $targets[$width] = max($targets[$width] ?? 0, $targetBytes);
        }

        if ($targets === []) {
            throw new RuntimeException('At least one image variant must be configured.');
        }

        ksort($targets, SORT_NUMERIC);
        $variants = [];

        try {
            foreach ($targets as $width => $targetBytes) {
                $variants[$width] = $this->encodeVariant($source, (int) $width, (int) $targetBytes, $sourceHeight);
            }
        } catch (Throwable $exception) {
            $this->cleanupTemporaryFiles($variants);

            throw $exception;
        }

        return $variants;
    }

    /**
     * @return array{temporary_path: string, width: int, height: int, file_size: int}
     */
    private function encodeVariant(\GdImage $source, int $width, int $targetBytes, int $sourceHeight): array
    {
        $sourceWidth = imagesx($source);
        $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
        $canvas = @imagecreatetruecolor($width, $height);

        if (! $canvas instanceof \GdImage) {
            throw new RuntimeException('Unable to allocate an image variant.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'alfikar-webp-');

        if (! is_string($temporaryPath)) {
            imagedestroy($canvas);

            throw new RuntimeException('Unable to create a temporary image file.');
        }

        @chmod($temporaryPath, 0600);

        try {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);

            if (! imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight)) {
                throw new RuntimeException('Unable to resize an image variant.');
            }

            $quality = max(0, min(100, (int) config('image-processing.quality', 78)));
            $minimumQuality = max(0, min($quality, (int) config('image-processing.minimum_quality', 64)));
            $fileSize = 0;

            do {
                if (! @imagewebp($canvas, $temporaryPath, $quality)) {
                    throw new RuntimeException('Unable to encode an image variant as WebP.');
                }

                clearstatcache(true, $temporaryPath);
                $fileSize = filesize($temporaryPath);

                if (! is_int($fileSize)) {
                    throw new RuntimeException('Unable to inspect an encoded image variant.');
                }

                if ($fileSize <= $targetBytes || $quality <= $minimumQuality) {
                    break;
                }

                $quality = max($minimumQuality, $quality - 4);
            } while (true);

            return [
                'temporary_path' => $temporaryPath,
                'width' => $width,
                'height' => $height,
                'file_size' => $fileSize,
            ];
        } catch (Throwable $exception) {
            $this->cleanupTemporaryFiles([['temporary_path' => $temporaryPath]]);

            throw $exception;
        } finally {
            imagedestroy($canvas);
        }
    }

    /**
     * @param  list<int>  $usedOrders
     */
    private function availableSortOrder(array $usedOrders, int $maxImages): int
    {
        for ($sortOrder = 0; $sortOrder < $maxImages; $sortOrder++) {
            if (! in_array($sortOrder, $usedOrders, true)) {
                return $sortOrder;
            }
        }

        $this->reject('Produk sudah mencapai batas jumlah foto.');
    }

    private function assertImageCapacity(Product $product, int $maxImages): void
    {
        if ($product->images()->count() >= $maxImages) {
            $this->reject('Produk sudah mencapai batas jumlah foto.');
        }
    }

    /**
     * @param  array<int, array{temporary_path: string}>  $variants
     */
    private function cleanupTemporaryFiles(array $variants): void
    {
        $failedDeletes = 0;

        foreach ($variants as $variant) {
            $path = $variant['temporary_path'] ?? null;

            if (is_string($path) && is_file($path) && ! @unlink($path)) {
                $failedDeletes++;
            }
        }

        if ($failedDeletes > 0) {
            Log::warning('Failed to remove temporary product image files.', [
                'file_count' => $failedDeletes,
            ]);
        }
    }

    /**
     * @param  list<string>  $paths
     */
    private function cleanupStoredFiles(string $diskName, array $paths, int $productId): void
    {
        $failedDeletes = 0;
        $disk = Storage::disk($diskName);

        foreach (array_unique($paths) as $path) {
            try {
                if ($disk->exists($path) && ! $disk->delete($path)) {
                    $failedDeletes++;
                }
            } catch (Throwable) {
                $failedDeletes++;
            }
        }

        if ($failedDeletes > 0) {
            Log::warning('Failed to clean stored image variants after an error.', [
                'resource_id' => $productId,
                'variant_count' => $failedDeletes,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function managedVariantPaths(ProductImage $image): array
    {
        $variants = $image->variants;

        if (! is_array($variants) || $variants === []) {
            throw new LogicException('The product image has no managed variants.');
        }

        $paths = [];

        foreach ($variants as $width => $path) {
            if (! is_string($path)) {
                throw new LogicException('The product image contains an invalid variant path.');
            }

            $this->assertManagedPath($image, $path);
            $filename = basename($path);
            $storedWidth = (int) substr($filename, 0, -5);

            if ((int) $width !== $storedWidth) {
                throw new LogicException('The product image variant width does not match its path.');
            }

            $paths[] = $path;
        }

        if (! in_array($image->path, $paths, true)) {
            throw new LogicException('The primary product image is not one of its managed variants.');
        }

        return array_values(array_unique($paths));
    }

    private function assertManagedPath(ProductImage $image, string $path): void
    {
        $prefix = 'products/'.$image->product_id.'/';

        if (! str_starts_with($path, $prefix) || str_contains($path, '..') || str_contains($path, '\\')) {
            throw new LogicException('The product image path is outside its resource directory.');
        }

        $segments = explode('/', substr($path, strlen($prefix)));

        if (
            count($segments) !== 2
            || ! Str::isUuid($segments[0])
            || ! preg_match('/\A[1-9]\d{0,3}\.webp\z/', $segments[1])
            || (int) substr($segments[1], 0, -5) > 1200
        ) {
            throw new LogicException('The product image path is not a managed WebP variant.');
        }
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['image' => $message]);
    }
}
