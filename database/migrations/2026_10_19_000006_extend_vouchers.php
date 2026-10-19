<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vouchers as in the Seller and Buyer Modules sheets:
     *  - kind: store (seller-funded), platform or shipping (BoomBuy-funded)
     *  - a start and end time, a cap for % vouchers, claimable or code-only,
     *    all products or selected ones, and End (ended_at)
     *  - voucher_claims: the buyer's wallet
     *  - orders: the platform and shipping vouchers used, besides the store one
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('kind', 10)->default('store')->after('seller_id');
            $table->decimal('max_discount', 10, 2)->nullable()->after('discount_value');
            $table->timestamp('starts_at')->nullable()->after('per_buyer_limit');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->timestamp('ended_at')->nullable()->after('ends_at');
            $table->string('display', 10)->default('claimable')->after('ended_at');
            $table->string('applies_to', 10)->default('all')->after('display');
        });

        Schema::create('voucher_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unique(['voucher_id', 'product_id']);
        });

        Schema::create('voucher_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['voucher_id', 'user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('platform_voucher_code', 30)->nullable()->after('discount_amount');
            $table->decimal('platform_discount', 10, 2)->default(0)->after('platform_voucher_code');
            $table->string('shipping_voucher_code', 30)->nullable()->after('platform_discount');
            $table->decimal('shipping_discount', 10, 2)->default(0)->after('shipping_voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['platform_voucher_code', 'platform_discount', 'shipping_voucher_code', 'shipping_discount']);
        });

        Schema::dropIfExists('voucher_claims');
        Schema::dropIfExists('voucher_products');

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['kind', 'max_discount', 'starts_at', 'ends_at', 'ended_at', 'display', 'applies_to']);
        });
    }
};
