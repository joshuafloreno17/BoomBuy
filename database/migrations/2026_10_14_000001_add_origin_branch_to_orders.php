<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The pickup side of branches: the rider brings the parcel to the branch
     * in the seller's town, which collects the day's parcels and sends them
     * to its Sorting Center.
     *
     * A branch may stand in its center's own town (Cebu City), so a town no
     * longer has to be unique among centers and branches.
     */
    public function up(): void
    {
        Schema::table('sorting_centers', function (Blueprint $table) {
            $table->dropUnique('unique_center_town');
            $table->index(['province', 'city_municipality']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('origin_branch_id')->nullable()->after('origin_center_id')->constrained('sorting_centers')->nullOnDelete();
            $table->timestamp('origin_branch_received_at')->nullable()->after('picked_up_at');
            $table->timestamp('sent_to_center_at')->nullable()->after('origin_branch_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_branch_id');
            $table->dropColumn(['origin_branch_received_at', 'sent_to_center_at']);
        });

        Schema::table('sorting_centers', function (Blueprint $table) {
            $table->dropIndex(['province', 'city_municipality']);
            $table->unique(['province', 'city_municipality'], 'unique_center_town');
        });
    }
};
