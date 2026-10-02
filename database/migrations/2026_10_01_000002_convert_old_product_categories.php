<?php

use App\Support\Categories;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Products saved before the 16-category system still carry old names
     * ("Smartphone", "Laptop"…). The product page then shows "SMARTPHONE"
     * under an "Electronics" breadcrumb, and admin category filters miss
     * them. Store the current label instead.
     */
    public function up(): void
    {
        $stored = DB::table('products')->distinct()->pluck('category')->filter();

        foreach ($stored as $old) {
            $label = Categories::label($old);

            if ($label !== null && $label !== $old) {
                DB::table('products')->where('category', $old)->update(['category' => $label]);
            }
        }
    }

    public function down(): void
    {
        // The old names aren't needed anywhere any more.
    }
};
