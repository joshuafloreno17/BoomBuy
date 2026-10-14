<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery branches, like a courier's local branch: a province's Sorting
     * Center sorts a parcel by town and sends it by truck to the branch in
     * the buyer's town, whose riders deliver it. A branch is a
     * sorting_centers row with parent_id = its province's center.
     */
    public function up(): void
    {
        Schema::table('sorting_centers', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('sorting_centers')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('current_center_id')->constrained('sorting_centers')->nullOnDelete();
            $table->timestamp('branch_dispatched_at')->nullable()->after('dispatched_at');
            $table->timestamp('at_branch_at')->nullable()->after('branch_dispatched_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['branch_dispatched_at', 'at_branch_at']);
        });

        Schema::table('sorting_centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
