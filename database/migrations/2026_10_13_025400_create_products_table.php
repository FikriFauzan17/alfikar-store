<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('author');
            $table->string('publisher');
            $table->string('isbn')->nullable()->unique();
            $table->longText('description');
            $table->json('specifications')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('wholesale_price')->nullable();
            $table->unsignedInteger('wholesale_minimum')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index(['category_id', 'is_published', 'published_at']);
            $table->index(['is_published', 'is_featured', 'published_at']);
            $table->index(['is_published', 'is_bestseller', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
