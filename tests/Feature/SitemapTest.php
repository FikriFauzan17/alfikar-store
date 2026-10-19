<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves only canonical public pages and published content in the sitemap', function () {
    $category = Category::factory()->create(['is_published' => true]);
    $publishedProduct = Product::factory()->for($category)->create([
        'slug' => 'sitemap-public-book',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    Product::factory()->for($category)->create([
        'slug' => 'sitemap-draft-book',
        'is_published' => false,
    ]);
    Product::factory()->for($category)->create([
        'slug' => 'sitemap-scheduled-book',
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);
    $publishedArticle = Article::factory()->create([
        'slug' => 'sitemap-public-article',
        'status' => ArticleStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    Article::factory()->create([
        'slug' => 'sitemap-draft-article',
        'status' => ArticleStatus::Draft,
    ]);
    Article::factory()->create([
        'slug' => 'sitemap-future-published-article',
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    $this->assertDatabaseHas('articles', [
        'id' => $publishedArticle->id,
        'status' => ArticleStatus::Published->value,
    ]);
    $publishedArticle = $publishedArticle->fresh();

    expect($publishedArticle->status)->toBe(ArticleStatus::Published)
        ->and($publishedArticle->published_at)->not->toBeNull()
        ->and($publishedArticle->published_at->lessThanOrEqualTo(now()))->toBeTrue()
        ->and(Article::published()->pluck('slug')->all())->toContain('sitemap-public-article');

    $response = $this->get(route('sitemap'));
    $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $xml = $response->streamedContent();

    expect(str_contains($xml, route('products.show', ['product' => $publishedProduct->slug])))->toBeTrue()
        ->and(str_contains($xml, route('articles.show', ['article' => $publishedArticle->slug])))->toBeTrue()
        ->and(str_contains($xml, 'sitemap-draft-book'))->toBeFalse()
        ->and(str_contains($xml, 'sitemap-scheduled-book'))->toBeFalse()
        ->and(str_contains($xml, 'sitemap-draft-article'))->toBeFalse()
        ->and(str_contains($xml, 'sitemap-future-published-article'))->toBeFalse()
        ->and(str_contains($xml, '?category='))->toBeFalse();

    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /produk?')
        ->assertSee(route('sitemap'));
});
