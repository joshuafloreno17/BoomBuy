<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store sales: a seller-funded discount on one product for a set time
     * (Upcoming → Ongoing → Ended). One sale per product at a time; a
     * running sale can be edited or ended early (ended_at).
     */
    public function up(): void
    {
        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('discount_type', 10);          // percent | fixed
            $table->decimal('discount_value', 12, 2);     // 20 (%) or the sale price (fixed)
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamp('ended_at')->nullable();    // End Now
            $table->timestamps();

            $table->index(['product_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
