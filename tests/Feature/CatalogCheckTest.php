<?php

namespace Tests\Feature;

use Tests\TestCase;

class CatalogCheckTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = database_path('seeders/catalog/ironclad');
    }

    protected function tearDown(): void
    {
        foreach (['hammer.png', 'Claw Hammer.jpg', 'Wrench (1).JPG'] as $file) {
            @unlink("{$this->dir}/{$file}");
        }
        parent::tearDown();
    }

    public function test_every_shop_in_the_list_has_a_folder_and_files_txt(): void
    {
        $list = require database_path('seeders/catalog/photo-list.php');

        $this->assertCount(16, $list);
        $this->assertSame(390, array_sum(array_map(fn ($shop) => count($shop['photos']), $list)));

        $this->artisan('boombuy:catalog-check')->assertSuccessful();

        foreach (array_keys($list) as $folder) {
            $this->assertFileExists(database_path("seeders/catalog/{$folder}/FILES.txt"));
        }
    }

    public function test_it_counts_good_photos_and_suggests_names_for_misnamed_ones(): void
    {
        $this->artisan('boombuy:catalog-check ironclad')->assertSuccessful();

        file_put_contents("{$this->dir}/hammer.png", 'x');
        file_put_contents("{$this->dir}/Claw Hammer.jpg", 'x');
        file_put_contents("{$this->dir}/Wrench (1).JPG", 'x');

        $this->artisan('boombuy:catalog-check ironclad')
            ->expectsOutputToContain('Wrench (1).JPG  →  palitan ng wrench')
            ->expectsOutputToContain('Claw Hammer.jpg  →  wala sa listahan')
            ->expectsOutputToContain('Kabuuan: 1 / 15 photos.')
            ->assertSuccessful();
    }

    public function test_an_unknown_shop_is_refused(): void
    {
        $this->artisan('boombuy:catalog-check nosuchshop')->assertFailed();
    }
}
