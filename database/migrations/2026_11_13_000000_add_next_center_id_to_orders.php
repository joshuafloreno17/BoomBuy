<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hub relay: while a parcel is In Transit, the next Sorting Center it is
 * trucked to (a hub on the way, or the buyer's center on the last leg).
 * Null = straight to destination_center_id, as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('next_center_id')->nullable()->after('destination_center_id');
            $table->index('next_center_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['next_center_id']);
            $table->dropColumn('next_center_id');
        });
    }
};
