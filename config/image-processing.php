<?php

return [
    'disk' => env('PRODUCT_IMAGE_DISK', 'public'),
    'max_images' => 5,
    'max_upload_bytes' => (int) env('PRODUCT_IMAGE_MAX_BYTES', 5 * 1024 * 1024),
    'max_pixels' => (int) env('PRODUCT_IMAGE_MAX_PIXELS', 20_000_000),
    'max_dimension' => (int) env('PRODUCT_IMAGE_MAX_DIMENSION', 12_000),
    'estimated_bytes_per_pixel' => 8,
    'memory_reserve_bytes' => 16 * 1024 * 1024,
    'max_estimated_memory_bytes' => (int) env('PRODUCT_IMAGE_MAX_ESTIMATED_MEMORY_BYTES', 192 * 1024 * 1024),
    'quality' => 78,
    'minimum_quality' => 64,
    'variants' => [
        320 => [
            'max_width' => 320,
            'target_bytes' => 40 * 1024,
        ],
        640 => [
            'max_width' => 640,
            'target_bytes' => 90 * 1024,
        ],
        1200 => [
            'max_width' => 1200,
            'target_bytes' => 200 * 1024,
        ],
    ],
];
