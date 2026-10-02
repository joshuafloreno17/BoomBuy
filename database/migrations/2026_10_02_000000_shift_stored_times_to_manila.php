<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The app used to run on UTC, so every stored time is 8 hours behind
 * Philippine time. The app and the DB session now run on Asia/Manila
 * (+08:00); this moves the existing times so they read correctly.
 *
 * TIMESTAMP columns were written through a session on the server's own
 * time zone, so they move by that server's offset (not always 8 hours).
 * DATETIME columns are stored as-is, so they move by the full 8 hours.
 * DATE columns (birthdate, voucher expiry) are calendar days and stay.
 */
return new class extends Migration
{
    private const SKIP_TABLES = ['migrations', 'failed_jobs'];

    public function up(): void
    {
        $this->shift(1);
    }

    public function down(): void
    {
        $this->shift(-1);
    }

    private function shift(int $direction): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }

        // The offset old sessions used (the server's default time zone), in minutes.
        DB::statement('SET time_zone = @@global.time_zone');
        $serverOffset = (int) DB::selectOne('SELECT TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), NOW()) AS m')->m;
        DB::statement("SET time_zone = '+08:00'");

        $columns = DB::select(
            "SELECT table_name AS t, column_name AS c, data_type AS d
               FROM information_schema.columns
              WHERE table_schema = DATABASE() AND data_type IN ('timestamp', 'datetime')"
        );

        $byTable = [];
        foreach ($columns as $column) {
            if (in_array($column->t, self::SKIP_TABLES)) {
                continue;
            }
            $minutes = $direction * ($column->d === 'timestamp' ? $serverOffset : 480);
            if ($minutes !== 0) {
                $byTable[$column->t][] = "`{$column->c}` = `{$column->c}` + INTERVAL {$minutes} MINUTE";
            }
        }

        foreach ($byTable as $table => $sets) {
            DB::statement("UPDATE `{$table}` SET " . implode(', ', $sets));
        }
    }
};
