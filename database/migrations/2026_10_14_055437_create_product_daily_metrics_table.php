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
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('products', function (Blueprint $table): void {
                $table->fullText(['title', 'author', 'publisher'], 'products_catalog_search_fulltext');
            });
        }

        Schema::create('product_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->unsignedBigInteger('product_views')->default(0);
            $table->unsignedBigInteger('whatsapp_clicks')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'metric_date'], 'product_daily_metrics_product_date_unique');
            $table->index('metric_date', 'product_daily_metrics_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_daily_metrics');

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropFullText('products_catalog_search_fulltext');
            });
        }

    }
};
