<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller payouts are not part of BoomBuy's ERP scope: the payout records and
 * the sellers' payout accounts go. (orders.commission_rate stays — the
 * commission reports use it.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('seller_payouts');

        if (Schema::hasColumn('seller_applications', 'payout_method')) {
            Schema::table('seller_applications', function (Blueprint $table) {
                $table->dropColumn(['payout_method', 'payout_account_name', 'payout_account_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->string('payout_method', 20)->nullable();
            $table->string('payout_account_name')->nullable();
            $table->string('payout_account_number', 60)->nullable();
        });

        Schema::create('seller_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20);
            $table->string('account_name')->nullable();
            $table->string('account_number', 60)->nullable();
            $table->string('reference');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->index(['seller_id', 'paid_at']);
        });
    }
};
