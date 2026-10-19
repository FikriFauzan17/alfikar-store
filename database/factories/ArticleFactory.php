<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title . '-' . fake()->unique()->numerify('#####')),
            'summary' => fake()->paragraph(),
            'body' => fake()->paragraphs(3, true),
            'image_path' => null,
            'image_alt' => null,
            'status' => ArticleStatus::Draft,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
