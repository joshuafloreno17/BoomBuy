<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Every Blade view starts with markup or a Blade directive. Stray script
 * lines pasted above <!DOCTYPE html> showed up as text at the top of
 * Admin → Logistics → Branches.
 */
class ViewFilesTest extends TestCase
{
    public function test_no_view_starts_with_stray_text(): void
    {
        $bad = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $first = ltrim((string) preg_replace('/^\xEF\xBB\xBF/', '', $file->getContents()));

            if ($first !== '' && !preg_match('/^(<|@|\{\{|\{!!)/', $first)) {
                $bad[] = $file->getRelativePathname() . ': ' . strtok($first, "\n");
            }
        }

        $this->assertSame([], $bad);
    }
}
