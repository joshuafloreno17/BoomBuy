<?php

namespace Tests\Feature;

use Tests\TestCase;

class CatalogCheckTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = database_path('seeders/catalog/evergreen');
    }

    protected function tearDown(): void
    {
        foreach (['clay-pot.png', 'Garden Rake.jpg', 'Fertilizer (1).JPG'] as $file) {
            @unlink("{$this->dir}/{$file}");
        }
        parent::tearDown();
    }

    public function test_every_shop_in_the_list_has_a_folder_and_files_txt(): void
    {
        $list = require database_path('seeders/catalog/photo-list.php');

        $this->assertCount(16, $list);
        $this->assertSame(397, array_sum(array_map(fn ($shop) => count($shop['photos']), $list)));

        $this->artisan('boombuy:catalog-check')->assertSuccessful();

        foreach (array_keys($list) as $folder) {
            $this->assertFileExists(database_path("seeders/catalog/{$folder}/FILES.txt"));
        }
    }

    public function test_it_counts_good_photos_and_suggests_names_for_misnamed_ones(): void
    {
        $this->artisan('boombuy:catalog-check evergreen')->assertSuccessful();

        file_put_contents("{$this->dir}/clay-pot.png", 'x');
        file_put_contents("{$this->dir}/Garden Rake.jpg", 'x');
        file_put_contents("{$this->dir}/Fertilizer (1).JPG", 'x');

        $this->artisan('boombuy:catalog-check evergreen')
            ->expectsOutputToContain('Fertilizer (1).JPG  →  palitan ng fertilizer')
            ->expectsOutputToContain('Garden Rake.jpg  →  wala sa listahan')
            ->expectsOutputToContain('Kabuuan: 20 / 20 photos.')
            ->assertSuccessful();
    }

    public function test_an_unknown_shop_is_refused(): void
    {
        $this->artisan('boombuy:catalog-check nosuchshop')->assertFailed();
    }
}
