<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flash sales (a flash price for a limited quantity in a time slot) and
     * bundle deals (any N items from some products for X% off, or buy X get
     * Y free). Seller-funded, like store sales.
     */
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('flash_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('sold')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'starts_at', 'ends_at']);
        });

        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('type', 10);                     // bundle | bxgy
            // bundle: any min_quantity items of the products for percent off
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedTinyInteger('percent')->nullable();
            // bxgy: buy buy_quantity of buy_product, get get_quantity of get_product free
            $table->foreignId('buy_product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('buy_quantity')->nullable();
            $table->foreignId('get_product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('get_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bundle_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unique(['bundle_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            // How this line was priced: flash, bundle, free (buy X get Y) — null for a regular line.
            $table->string('price_note', 40)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('price_note'));
        Schema::dropIfExists('bundle_products');
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('flash_sales');
    }
};
