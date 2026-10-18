<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A store's own categories ("Bags", "Home Decor > Lamps"), separate from
     * the platform categories: at most 2 levels, a product in up to 3.
     */
    public function up(): void
    {
        Schema::create('store_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('store_categories')->cascadeOnDelete();
            $table->string('name', 50);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index(['seller_id', 'parent_id', 'sort_order']);
        });

        Schema::create('product_store_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('store_category_id')->constrained('store_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'store_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_store_category');
        Schema::dropIfExists('store_categories');
    }
};
