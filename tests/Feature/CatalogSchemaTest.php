<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('relates products and photos to their category', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();
    $image = ProductImage::factory()->for($product)->create();

    expect($product->category->getKey())->toBe($category->getKey())
        ->and($product->images)->toHaveCount(1)
        ->and($image->product->getKey())->toBe($product->getKey());
});

it('enforces unique product codes and slugs', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['code' => 'ALF-0001', 'slug' => 'book-one']);

    expect(fn() => Product::factory()->for($category)->create([
        'code' => 'ALF-0001',
        'slug' => 'book-two',
    ]))->toThrow(QueryException::class);

    expect(fn() => Product::factory()->for($category)->create([
        'code' => 'ALF-0002',
        'slug' => 'book-one',
    ]))->toThrow(QueryException::class);
});

it('prevents deleting a category that still has products', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    expect(fn() => $category->delete())->toThrow(QueryException::class);
});

it('excludes drafts and future scheduled articles from the published scope', function () {
    Article::factory()->create(['status' => ArticleStatus::Draft]);
    Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'published_at' => now()->addDay(),
    ]);
    $published = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    expect(Article::published()->pluck('id')->all())->toBe([$published->id]);
});

it('keeps administrator access false and outside mass assignment by default', function () {
    $user = User::factory()->create();
    $user->fill(['is_admin' => true]);

    expect($user->fresh()->is_admin)->toBeFalse();
});
