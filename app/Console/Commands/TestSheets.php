<?php

namespace App\Console\Commands;

use App\Services\TestSheetData;
use Illuminate\Console\Command;

/**
 * The black-box test sheets' Test Data on this database (App\Services\TestSheetData):
 *   php artisan boombuy:test-sheets seed   — before the Module tests (again before each module)
 *   php artisan boombuy:test-sheets clear  — before the Account Creation tests
 * Same as Admin → Settings → Test Sheet Data.
 */
class TestSheets extends Command
{
    protected $signature = 'boombuy:test-sheets {action : seed or clear} {--force : Skip the confirmation question}';

    protected $description = 'Seed (or clear) the black-box test sheets\' Test Data: @example.com accounts, products, vouchers, zones';

    public function handle(TestSheetData $data): int
    {
        $action = $this->argument('action');

        if (!in_array($action, ['seed', 'clear'], true)) {
            $this->error('Use "seed" or "clear".');

            return self::INVALID;
        }

        if ($action === 'clear' && !$this->option('force')
            && !$this->confirm('Remove every @example.com account with everything it made, the sheet vouchers and zones, and the test mailbox?')) {
            return self::SUCCESS;
        }

        $log = $action === 'seed' ? $data->seed() : $data->clear();

        $this->table(['What', 'Result'], collect($log)->map(fn ($v, $k) => [$k, $v])->values()->all());
        $this->info($action === 'seed' ? 'Ready for the Module tests.' : 'Ready for the Account Creation tests.');

        return self::SUCCESS;
    }
}
