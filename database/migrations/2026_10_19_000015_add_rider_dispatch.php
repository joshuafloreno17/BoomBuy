<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Rider & Logistics Modules: riders go Online/Offline and get job offers
 * (60 seconds each, at most 3 tries, then the Unassigned queue); delivery
 * OTP and COD amount at the door; Return to Sender; rider pay per pickup;
 * COD remittances the logistics company confirms; Lost / Damaged parcels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_online')->default(false)->after('status');
            $table->timestamp('online_at')->nullable()->after('is_online');
        });

        Schema::table('rider_applications', function (Blueprint $table) {
            $table->boolean('needs_reverification')->default(false)->after('status');
            $table->string('suspension_reason', 300)->nullable()->after('needs_reverification');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_otp', 6)->nullable();
            $table->unsignedTinyInteger('otp_failures')->default(0);
            $table->timestamp('otp_locked_until')->nullable();
            $table->decimal('cod_amount_collected', 12, 2)->nullable();
            $table->decimal('pickup_fee', 10, 2)->nullable();
            $table->timestamp('dispatch_unassigned_at')->nullable();
            $table->timestamp('rts_at')->nullable();
            $table->foreignId('rts_rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('exception_reason', 300)->nullable();
            $table->string('exception_photo')->nullable();
            $table->timestamp('exception_at')->nullable();
        });

        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 12)->default('pending'); // pending | accepted | declined | expired | cancelled
            $table->timestamp('offered_at');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['rider_id', 'status']);
        });

        Schema::create('cod_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('expected', 12, 2);
            $table->json('order_ids');
            $table->string('status', 12)->default('Pending'); // Pending | Confirmed
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_remittances');
        Schema::dropIfExists('job_offers');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rts_rider_id');
            $table->dropColumn(['delivery_otp', 'otp_failures', 'otp_locked_until', 'cod_amount_collected', 'pickup_fee', 'dispatch_unassigned_at', 'rts_at', 'exception_reason', 'exception_photo', 'exception_at']);
        });

        Schema::table('rider_applications', function (Blueprint $table) {
            $table->dropColumn(['needs_reverification', 'suspension_reason']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_online', 'online_at']);
        });
    }
};
