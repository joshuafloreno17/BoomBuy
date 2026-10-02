<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Complaint and return/refund evidence used to be saved on the public
     * disk (reachable by anyone with the link). Move the existing files to
     * the private local disk; they're now served by EvidenceController to
     * the people involved only. Paths stay the same, so no column changes.
     */
    public function up(): void
    {
        $paths = DB::table('complaints')->whereNotNull('evidence')->pluck('evidence')
            ->merge(DB::table('return_refund_requests')->whereNotNull('evidence')->pluck('evidence'))
            ->filter()
            ->unique();

        foreach ($paths as $path) {
            $path = ltrim($path, '/');

            if (!Storage::disk('public')->exists($path) || Storage::disk('local')->exists($path)) {
                continue;
            }

            Storage::disk('local')->put($path, Storage::disk('public')->get($path));
            Storage::disk('public')->delete($path);
        }
    }

    public function down(): void
    {
        // Files stay private; EvidenceController can still read either disk.
    }
};
