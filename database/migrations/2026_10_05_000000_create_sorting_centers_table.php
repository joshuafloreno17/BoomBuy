<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One Sorting Center per city/municipality. A seller drops a parcel at the
 * center of their own town (origin); it travels to the center of the buyer's
 * town (destination), where a rider takes it to the buyer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorting_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('province');
            $table->string('city_municipality');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['province', 'city_municipality'], 'unique_center_town');
        });

        // The center a logistics account works at. Empty = head office, sees every center.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sorting_center_id')->nullable()->after('role')
                ->constrained('sorting_centers')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_province')->nullable()->after('shipping_address');
            $table->string('shipping_city')->nullable()->after('shipping_province');
            $table->foreignId('origin_center_id')->nullable()->after('delivery_rider_id')
                ->constrained('sorting_centers')->nullOnDelete();
            $table->foreignId('destination_center_id')->nullable()->after('origin_center_id')
                ->constrained('sorting_centers')->nullOnDelete();
            // Where the parcel physically is right now (null while still with the seller or a rider).
            $table->foreignId('current_center_id')->nullable()->after('destination_center_id')
                ->constrained('sorting_centers')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable()->after('sorting_center_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_center_id');
            $table->dropConstrainedForeignId('destination_center_id');
            $table->dropConstrainedForeignId('current_center_id');
            $table->dropColumn(['shipping_province', 'shipping_city', 'dispatched_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sorting_center_id');
        });

        Schema::dropIfExists('sorting_centers');
    }
};
