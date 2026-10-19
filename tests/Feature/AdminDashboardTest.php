<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows dashboard counts from catalog content and authorized users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $anotherAdmin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create(['is_published' => true]);

    Product::factory()->create([
        'category_id' => $category->id,
        'is_published' => true,
        'stock' => 4,
    ]);
    Product::factory()->create([
        'category_id' => $category->id,
        'is_published' => true,
        'stock' => 0,
    ]);
    Product::factory()->create([
        'category_id' => $category->id,
        'is_published' => false,
        'stock' => 0,
    ]);
    Article::factory()->create(['status' => ArticleStatus::Draft]);
    Article::factory()->create(['status' => ArticleStatus::Published]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Ringkasan Alfikar')
        ->assertSee('Navigasi admin')
        ->assertSee('data-stat="published-products">2', false)
        ->assertSee('data-stat="out-of-stock-products">2', false)
        ->assertSee('data-stat="articles">2', false)
        ->assertSee('data-stat="users">2', false)
        ->assertSee('Pengguna');
});

it('denies non-admin users access to dashboard and its statistics', function () {
    $nonAdmin = User::factory()->create();

    $this->actingAs($nonAdmin)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('redirects guests from the dashboard to login', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});
