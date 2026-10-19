<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
        $this->markTestSkipped('GD with WebP and EXIF support is required for image processing tests.');
    }

    Storage::fake('public');
});

function makeAlfikarPngUpload(int $width, int $height): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 4, 78, 59, 0));
    imagefilledrectangle(
        $image,
        (int) ($width / 3),
        (int) ($height / 3),
        (int) (($width * 2) / 3),
        (int) (($height * 2) / 3),
        imagecolorallocatealpha($image, 198, 167, 94, 0),
    );

    ob_start();
    $encoded = imagepng($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    if (! $encoded || ! is_string($contents)) {
        throw new RuntimeException('Unable to create a PNG test upload.');
    }

    return UploadedFile::fake()->createWithContent('client-cover.png', $contents);
}

function makeAlfikarOrientedJpegUpload(int $width, int $height, int $orientation): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 4, 78, 59));

    ob_start();
    $encoded = imagejpeg($image, null, 90);
    $contents = ob_get_clean();
    imagedestroy($image);

    if (! $encoded || ! is_string($contents)) {
        throw new RuntimeException('Unable to create a JPEG test upload.');
    }

    $tiff = 'II' . pack('v', 42) . pack('V', 8) . pack('v', 1)
        . pack('v', 0x0112) . pack('v', 3) . pack('V', 1) . pack('v', $orientation)
        . pack('v', 0) . pack('V', 0);
    $exif = "Exif\0\0" . $tiff;
    $app1 = "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif;
    $contents = substr($contents, 0, 2) . $app1 . substr($contents, 2);

    return UploadedFile::fake()->createWithContent('client-photo.jpg', $contents);
}

it('stores readable adaptive WebP variants without retaining the uploaded original', function () {
    $product = Product::factory()->create();
    $upload = makeAlfikarPngUpload(1600, 900);
    $image = app(ProductImageProcessor::class)->storeFor($product, $upload, 'Sampul buku');

    expect(array_keys($image->variants))->toBe([320, 640, 1200])
        ->and($image->disk)->toBe('public')
        ->and($image->path)->toBe($image->variants[1200])
        ->and($image->width)->toBe(1200)
        ->and($image->height)->toBe(675)
        ->and($image->mime_type)->toBe('image/webp')
        ->and($image->alt_text)->toBe('Sampul buku');

    $targets = [320 => 40 * 1024, 640 => 90 * 1024, 1200 => 200 * 1024];
    $totalBytes = 0;

    foreach ($image->variants as $width => $path) {
        $storedPath = Storage::disk('public')->path($path);
        $size = Storage::disk('public')->size($path);
        $info = getimagesize($storedPath);
        $decoded = imagecreatefromwebp($storedPath);

        expect($info)->not->toBeFalse()
            ->and($info['mime'])->toBe('image/webp')
            ->and($info[0])->toBe($width)
            ->and($info[0])->toBeLessThanOrEqual(1200)
            ->and($size)->toBeLessThanOrEqual($targets[$width])
            ->and($decoded)->toBeInstanceOf(GdImage::class);

        $center = imagecolorsforindex($decoded, imagecolorat($decoded, (int) ($width / 2), (int) ($info[1] / 2)));
        expect($center['red'])->toBeGreaterThan(150)
            ->and($center['green'])->toBeGreaterThan(120);

        imagedestroy($decoded);
        $totalBytes += $size;
    }

    expect($image->file_size)->toBe($totalBytes)
        ->and(Storage::disk('public')->allFiles())->toHaveCount(3);
});

it('does not upscale a small source image', function () {
    $product = Product::factory()->create();
    $image = app(ProductImageProcessor::class)->storeFor($product, makeAlfikarPngUpload(180, 120));

    expect(array_keys($image->variants))->toBe([180])
        ->and($image->width)->toBe(180)
        ->and($image->height)->toBe(120);
});

it('corrects JPEG EXIF orientation before creating variants', function () {
    $product = Product::factory()->create();
    $upload = makeAlfikarOrientedJpegUpload(100, 50, 6);
    $image = app(ProductImageProcessor::class)->storeFor($product, $upload);
    $encodedWebp = Storage::disk('public')->get($image->path);

    expect($image->width)->toBe(50)
        ->and($image->height)->toBe(100)
        ->and(array_keys($image->variants))->toBe([50])
        ->and($encodedWebp)->not->toContain('EXIF');
});

it('rejects spoofed files, files above the byte limit, and images above the pixel limit', function () {
    $product = Product::factory()->create();
    $processor = app(ProductImageProcessor::class);
    $spoofedFile = UploadedFile::fake()->createWithContent('script.png', '<script>not an image</script>');

    expect(fn() => $processor->storeFor($product, $spoofedFile))
        ->toThrow(ValidationException::class);

    config(['image-processing.max_upload_bytes' => 16]);
    expect(fn() => $processor->storeFor($product, makeAlfikarPngUpload(20, 20)))
        ->toThrow(ValidationException::class);

    config(['image-processing.max_upload_bytes' => 5 * 1024 * 1024]);
    config(['image-processing.max_pixels' => 100]);
    expect(fn() => $processor->storeFor($product, makeAlfikarPngUpload(20, 20)))
        ->toThrow(ValidationException::class);

    expect(ProductImage::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('counts existing photos toward the five image limit', function () {
    $product = Product::factory()->create();
    $processor = app(ProductImageProcessor::class);

    foreach (range(0, 3) as $sortOrder) {
        ProductImage::factory()->for($product)->create(['sort_order' => $sortOrder]);
    }

    $processor->storeFor($product, makeAlfikarPngUpload(40, 30));

    expect($product->images()->count())->toBe(5);
    expect(fn() => $processor->storeFor($product, makeAlfikarPngUpload(40, 30)))
        ->toThrow(ValidationException::class);
    expect($product->images()->count())->toBe(5);
});

it('cleans variants when decoding fails and rejects unsafe deletion paths', function () {
    $product = Product::factory()->create();
    $processor = app(ProductImageProcessor::class);
    $validPng = makeAlfikarPngUpload(30, 20);
    $truncatedPng = UploadedFile::fake()->createWithContent(
        'broken.png',
        substr((string) file_get_contents($validPng->getRealPath()), 0, 33),
    );

    expect(fn() => $processor->storeFor($product, $truncatedPng))
        ->toThrow(ValidationException::class);
    expect(Storage::disk('public')->allFiles())->toBeEmpty()
        ->and(ProductImage::query()->count())->toBe(0);

    Storage::disk('public')->put('keep.txt', 'unrelated');
    $unsafeImage = ProductImage::factory()->for($product)->create([
        'path' => '../keep.txt',
        'variants' => [320 => '../keep.txt'],
    ]);

    expect(fn() => $processor->delete($unsafeImage))->toThrow(LogicException::class);
    expect(Storage::disk('public')->exists('keep.txt'))->toBeTrue();
    $this->assertModelExists($unsafeImage);
});

it('deletes all owned variants and bounds PHP memory for one image', function () {
    $product = Product::factory()->create();
    $processor = app(ProductImageProcessor::class);
    $upload = makeAlfikarPngUpload(900, 600);

    memory_reset_peak_usage();
    $startingMemory = memory_get_usage(true);
    $image = $processor->storeFor($product, $upload);
    $peakMemoryIncrease = max(0, memory_get_peak_usage(true) - $startingMemory);

    expect($peakMemoryIncrease)->toBeLessThan(64 * 1024 * 1024);

    $paths = array_values($image->variants);
    $processor->delete($image);

    foreach ($paths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }

    $this->assertModelMissing($image);
});

it('keeps conservative default image bounds and quality', function () {
    expect(config('image-processing.max_upload_bytes'))->toBe(5 * 1024 * 1024)
        ->and(config('image-processing.max_pixels'))->toBe(20_000_000)
        ->and(config('image-processing.quality'))->toBeBetween(75, 80)
        ->and(config('image-processing.minimum_quality'))->toBeGreaterThanOrEqual(60);
});
