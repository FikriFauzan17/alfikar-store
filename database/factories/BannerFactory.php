<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'eyebrow' => null,
            'title' => fake()->sentence(4),
            'description' => null,
            'image_path' => null,
            'image_alt' => null,
            'link_url' => null,
            'link_label' => null,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 0,
            'is_active' => false,
        ];
    }
}
