<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'code' => strtoupper(fake()->unique()->bothify('BK-####??')),
            'slug' => Str::slug($title . '-' . fake()->unique()->numerify('#####')),
            'title' => $title,
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'isbn' => null,
            'description' => fake()->paragraph(),
            'specifications' => null,
            'stock' => 0,
            'unit_price' => fake()->numberBetween(10000, 250000),
            'wholesale_price' => null,
            'wholesale_minimum' => null,
            'is_published' => false,
            'is_featured' => false,
            'is_bestseller' => false,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
