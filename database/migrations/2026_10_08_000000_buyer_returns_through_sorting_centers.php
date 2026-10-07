<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer returns and refunds, end to end:
 *  - where the refund goes (the buyer's GCash, Maya or bank account);
 *  - a returned item travels like a parcel: dropped at the buyer's Sorting
 *    Center, sent on to the seller's, handed to the seller;
 *  - BoomBuy (the admin) sends the refund, since it holds the money until
 *    the return window closes, and records its reference;
 *  - a rejected request can be sent to BoomBuy for review (disputed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_refund_requests', function (Blueprint $table) {
            $table->string('refund_method', 20)->nullable()->after('refund_amount');
            $table->string('refund_account_name')->nullable()->after('refund_method');
            $table->string('refund_account_number', 60)->nullable()->after('refund_account_name');
            $table->string('refund_reference')->nullable()->after('refund_account_number');
            $table->timestamp('refunded_at')->nullable();
            $table->unsignedBigInteger('refunded_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dropped_off_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('restocked_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('current_center_id')->nullable()->constrained('sorting_centers')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->index(['status', 'updated_at']);
        });

        // The seller no longer sends refunds; ones they had started now wait for BoomBuy.
        DB::table('return_refund_requests')->where('status', 'refund_processing')->update(['status' => 'refund_pending']);
    }

    public function down(): void
    {
        DB::table('return_refund_requests')->where('status', 'refund_pending')->update(['status' => 'refund_processing']);

        Schema::table('return_refund_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'updated_at']);
            $table->dropConstrainedForeignId('current_center_id');
            $table->dropColumn([
                'refund_method', 'refund_account_name', 'refund_account_number', 'refund_reference', 'refunded_at',
                'refunded_by', 'approved_at', 'dropped_off_at', 'returned_at', 'restocked_at', 'escalated_at',
                'closed_at', 'admin_note',
            ]);
        });
    }
};
