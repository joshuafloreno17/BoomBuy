<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A 6-digit code only the buyer sees, asked for at the Sorting Center
 * counter before a pick-up order is handed over — order numbers are short
 * and easy to guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_code', 6)->nullable()->after('fulfillment');
        });

        // Pick-up orders still on their way or waiting get a code now.
        DB::table('orders')
            ->where('fulfillment', 'pickup')
            ->whereNotIn('status', ['Delivered', 'Cancelled', 'Returning', 'Return Ready', 'Returned to Seller'])
            ->pluck('id')
            ->each(fn ($id) => DB::table('orders')->where('id', $id)->update([
                'pickup_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            ]));
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pickup_code');
        });
    }
};
