<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Buyers no longer wait for an admin. Anyone still waiting is let in now.
 * (Rejected buyers stay rejected; suspended accounts stay suspended.)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('buyer_applications')
            ->where('status', 'Pending Verification')
            ->update([
                'status' => 'Approved',
                'admin_remarks' => 'Approved automatically — buyers no longer need admin approval.',
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Not reversible: we can't tell which ones were waiting.
    }
};
