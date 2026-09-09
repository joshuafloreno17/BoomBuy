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
        Schema::create('return_refund_requests', function (Blueprint $table) {
            $table->id();

            // Order na nire-return/refund
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Product/item na nire-return
            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();

            // Buyer na nag-request
            $table->foreignId('buyer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Seller ng product
            $table->foreignId('seller_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // return or refund
            $table->string('request_type');

            // Reason ng buyer
            $table->string('reason');

            // Optional explanation
            $table->text('message')->nullable();

            // Optional evidence/photo path
            $table->string('evidence')->nullable();

            // pending, approved, rejected, returned, refund_processing, refunded
            $table->string('status')->default('pending');

            // Amount na ire-refund
            $table->decimal('refund_amount', 12, 2)->default(0);

            // Seller/admin response
            $table->text('seller_note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_refund_requests');
    }
};