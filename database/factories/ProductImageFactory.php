<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'disk' => 'public',
            'path' => 'test/' . fake()->uuid() . '.webp',
            'alt_text' => fake()->sentence(4),
            'variants' => null,
            'width' => 640,
            'height' => 900,
            'file_size' => 24000,
            'mime_type' => 'image/webp',
            'sort_order' => 0,
        ];
    }
}
