<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'setting_key' => fake()->unique()->slug(2),
            'setting_value' => fake()->sentence(),
            'value_type' => 'string',
            'is_public' => false,
            'updated_by' => null,
        ];
    }
}
