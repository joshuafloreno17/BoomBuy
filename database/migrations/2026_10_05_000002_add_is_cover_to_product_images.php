<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The seller can pick any photo as the product's cover (the Shop thumbnail).
 * Without a pick, the cover is chosen automatically (App\Support\ProductPhotos::sync).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_images', 'is_cover')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->boolean('is_cover')->default(false)->after('sort_order');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('is_cover');
        });
    }
};
