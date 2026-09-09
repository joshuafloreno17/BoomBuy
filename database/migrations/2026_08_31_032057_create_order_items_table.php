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
        Schema::create('order_items', function (Blueprint $table) {

            $table->id();

            // Order na kinabibilangan ng item
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Product na binili
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Seller ng product
            $table->foreignId('seller_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Product information noong binili
            $table->string('product_name');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};