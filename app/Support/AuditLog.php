<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Who changed what and when (rate changes, status overrides…). */
class AuditLog
{
    public static function record(string $action, ?string $subjectType = null, ?int $subjectId = null, array $details = [], ?int $userId = null): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $userId ?? (session('user')['id'] ?? null),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'details' => $details ? json_encode($details) : null,
            'created_at' => now(),
        ]);
    }

    public static function for(string $subjectType, int $subjectId)
    {
        return DB::table('audit_logs')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('id')
            ->get();
    }
}
