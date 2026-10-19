<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Checks the product photos in database/seeders/catalog/<shop>/ against the
 * list in database/seeders/catalog/photo-list.php (the "BoomBuy Catalog Plan"
 * doc): what is there, what is missing, and which files are misnamed.
 * Also makes any missing shop folder and (re)writes its FILES.txt.
 *
 *   php artisan boombuy:catalog-check             — every shop
 *   php artisan boombuy:catalog-check gentryandco — one shop, every missing name
 */
class CatalogCheck extends Command
{
    protected $signature = 'boombuy:catalog-check {shop? : Folder name, e.g. gentryandco}';

    protected $description = 'Check the catalog photo folders: done, missing and misnamed photos per shop';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function handle(): int
    {
        $root = database_path('seeders/catalog');
        $list = require $root . '/photo-list.php';
        $only = $this->argument('shop');

        if ($only !== null && !isset($list[$only])) {
            $this->error("No shop folder \"{$only}\". Use one of: " . implode(', ', array_keys($list)));

            return self::INVALID;
        }

        $rows = [];
        $totalHave = $totalNeed = 0;

        foreach ($list as $folder => $shop) {
            if ($only !== null && $folder !== $only) {
                continue;
            }

            $dir = "{$root}/{$folder}";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->writeFilesTxt($dir, $shop);

            [$have, $missing, $wrong] = $this->compare($dir, $shop);
            $need = count($shop['photos']);
            $totalHave += count($have);
            $totalNeed += $need;

            $rows[] = [$folder, $shop['shop'], count($have) . " / {$need}", $missing ? count($missing) : '-', $wrong ? count($wrong) : '-'];

            if ($wrong) {
                $this->newLine();
                $this->warn("{$folder}: mali ang pangalan (wrong name)");
                foreach ($wrong as $file => $hint) {
                    $this->line("  {$file}" . ($hint ? "  →  palitan ng {$hint}" : '  →  wala sa listahan'));
                }
            }

            if ($missing && ($only !== null || count($missing) <= 5)) {
                $this->newLine();
                $this->line("<comment>{$folder}: kulang pa (missing)</comment>");
                foreach ($missing as $name) {
                    $this->line("  {$name}");
                }
            }
        }

        $this->newLine();
        $this->table(['Folder', 'Shop', 'Meron na', 'Kulang', 'Mali ang pangalan'], $rows);
        $this->info("Kabuuan: {$totalHave} / {$totalNeed} photos.");

        if ($only === null) {
            $this->line('Para makita ang lahat ng kulang sa isang shop: php artisan boombuy:catalog-check <folder>');
        }

        return self::SUCCESS;
    }

    /** @return array{0: list<string>, 1: list<string>, 2: array<string, ?string>} have, missing, wrong file => suggested name */
    private function compare(string $dir, array $shop): array
    {
        $wanted = $shop['photos'];
        $known = array_merge($wanted, $shop['done'] ?? []);
        $have = $wrong = [];

        foreach (scandir($dir) as $file) {
            if ($file[0] === '.' || $file === 'FILES.txt' || is_dir("{$dir}/{$file}")) {
                continue;
            }

            $name = pathinfo($file, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (in_array($ext, self::EXTENSIONS, true) && in_array($name, $known, true)) {
                if (in_array($name, $wanted, true)) {
                    $have[$name] = true;
                }
                continue;
            }

            // "Oxford Shirt White.JPG", "oxford_shirt_white (1).png" → oxford-shirt-white
            $guess = Str::of($name)->lower()->replaceMatches('/\(\d+\)/', '')->replaceMatches('/[\s_]+/', '-')->trim('-')->toString();
            $wrong[$file] = in_array($guess, $known, true) && in_array($ext, self::EXTENSIONS, true) ? $guess : null;
        }

        $missing = array_values(array_filter($wanted, fn ($name) => !isset($have[$name])));

        return [array_keys($have), $missing, $wrong];
    }

    private function writeFilesTxt(string $dir, array $shop): void
    {
        $lines = [
            "{$shop['shop']} — " . count($shop['photos']) . ' photos',
            'I-save ang bawat photo sa folder na ito gamit ang eksaktong pangalan sa ibaba.',
            'Puwede ang .jpg, .png o .webp. Isang photo bawat pangalan.',
            'I-check: php artisan boombuy:catalog-check ' . basename($dir),
            '',
            ...$shop['photos'],
        ];

        file_put_contents("{$dir}/FILES.txt", implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
