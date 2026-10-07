<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The class's ERP Flow statuses: "Processing" splits into Confirmed (seller
 * accepted) and Preparing — orders already being processed become
 * Preparing; delivered orders the buyer already confirmed become Completed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'Processing')->update(['status' => 'Preparing']);

        DB::table('orders')
            ->where('status', 'Delivered')
            ->whereNotNull('buyer_received_at')
            ->update(['status' => 'Completed']);
    }

    public function down(): void
    {
        DB::table('orders')->whereIn('status', ['Confirmed', 'Preparing'])->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'Completed')->update(['status' => 'Delivered']);
        DB::table('orders')->where('status', 'Sorted')->update(['status' => 'At Sorting Center']);
    }
};
