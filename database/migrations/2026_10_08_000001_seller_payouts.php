<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paying sellers:
 *  - orders.commission_rate: BoomBuy's cut as it was when the order was
 *    placed, so changing the rate in Settings doesn't rewrite past earnings;
 *  - where each seller wants to be paid (GCash, Maya or bank account);
 *  - seller_payouts: every payment the admin sends, with its reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('delivery_fee');
        });

        $rate = (float) (DB::table('platform_settings')->where('key', 'commission_rate')->value('value') ?? 10);
        DB::table('orders')->update(['commission_rate' => $rate]);

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

    public function down(): void
    {
        Schema::dropIfExists('seller_payouts');

        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropColumn(['payout_method', 'payout_account_name', 'payout_account_number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
