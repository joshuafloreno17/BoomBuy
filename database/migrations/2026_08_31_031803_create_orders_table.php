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
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            // Buyer who placed the order
            $table->foreignId('buyer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Order total
            $table->decimal('total_amount', 12, 2)
                ->default(0);

            // Order status
            $table->string('status')
                ->default('pending');

            // Delivery / customer information
            $table->string('shipping_name')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->text('shipping_address')->nullable();

            // Payment method
            $table->string('payment_method')
                ->default('Cash on Delivery');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};