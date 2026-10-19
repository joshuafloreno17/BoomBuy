<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Buyers following stores, and an optional most-per-buyer on a product (B4, B5). */
    public function up(): void
    {
        Schema::create('store_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'seller_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('purchase_limit')->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('purchase_limit'));
        Schema::dropIfExists('store_follows');
    }
};
