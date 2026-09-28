<?php

use App\Models\ProductVariation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time cleanup so existing rows match what ProductVariation now saves
     * ("Color", not "color" / "coloer"). Order history is untouched — restock
     * and reorder match labels case-insensitively, and "coloer" rows were
     * already fixed by hand.
     */
    public function up(): void
    {
        DB::table('product_variations')->orderBy('id')->each(function ($row) {

            $type = ProductVariation::normalizeType((string) $row->variation_type);
            $value = ProductVariation::normalizeValue((string) $row->variation_value);

            if ($type !== $row->variation_type || $value !== $row->variation_value) {
                DB::table('product_variations')
                    ->where('id', $row->id)
                    ->update(['variation_type' => $type, 'variation_value' => $value]);
            }
        });
    }

    public function down(): void
    {
        // Not reversible (the original spellings aren't kept) — and nothing
        // depends on them.
    }
};
