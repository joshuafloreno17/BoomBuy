<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A seller asking for their Available balance (Seller Modules A15):
     * at least ₱100.00, at most what is available, one pending at a time.
     */
    public function up(): void
    {
        Schema::create('seller_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 12)->default('Pending'); // Pending | Paid | Cancelled
            $table->string('method', 20)->nullable();
            $table->string('account_name', 100)->nullable();
            $table->string('account_number', 40)->nullable();
            $table->string('reference', 60)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_payout_requests');
    }
};
