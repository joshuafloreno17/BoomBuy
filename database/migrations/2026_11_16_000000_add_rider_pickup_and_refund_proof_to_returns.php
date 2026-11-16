<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Returns, round two:
 *  - the buyer chooses how the item goes back: they bring it to their
 *    Sorting Center (dropoff) or a rider collects it from them (pickup);
 *  - the rider's photo when they collect it;
 *  - BoomBuy's screenshot of the refund transfer, and the buyer's
 *    "I didn't receive it" report with BoomBuy's answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_refund_requests', function (Blueprint $table) {
            $table->string('return_method', 10)->nullable()->after('request_type');
            $table->foreignId('pickup_rider_id')->nullable()->after('return_method')->constrained('users')->nullOnDelete();
            $table->timestamp('pickup_assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->string('pickup_proof')->nullable();
            $table->string('refund_proof')->nullable()->after('refund_reference');
            $table->timestamp('refund_issue_at')->nullable();
            $table->string('refund_issue_note', 500)->nullable();
            $table->timestamp('refund_issue_resolved_at')->nullable();
            $table->string('refund_issue_answer', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('return_refund_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_rider_id');
            $table->dropColumn([
                'return_method', 'pickup_assigned_at', 'picked_up_at', 'pickup_proof', 'refund_proof',
                'refund_issue_at', 'refund_issue_note', 'refund_issue_resolved_at', 'refund_issue_answer',
            ]);
        });
    }
};
