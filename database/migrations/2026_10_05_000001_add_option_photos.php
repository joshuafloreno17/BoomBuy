<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Photos per option: a product photo can belong to one option (or to all,
 * when product_variation_id is null). The cover (products.image) and each
 * option's chip photo (product_variations.image) are picked from these.
 * Order lines keep the photo of the option that was bought.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Checked first so a run that stopped halfway can simply run again.
        if (!Schema::hasColumn('product_images', 'product_variation_id')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->foreignId('product_variation_id')->nullable()->after('product_id')
                    ->constrained('product_variations')->nullOnDelete();
            });
        }

        // "Move first" puts a photo before the others, so the order can go below 0.
        Schema::table('product_images', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->change();
        });

        if (!Schema::hasColumn('order_items', 'variation_image')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('variation_image')->nullable()->after('variation_label');
            });
        }

        // Existing photos join the new rows: the old cover first among the
        // photos for all options, each option's old photo as that option's photo.
        $now = now();

        foreach (DB::table('products')->whereNotNull('image')->get(['id', 'image']) as $product) {
            $known = DB::table('product_images')->where('product_id', $product->id)->where('path', $product->image)->exists();

            if (!$known) {
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'path' => $product->image,
                    'sort_order' => -1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (DB::table('product_variations')->whereNotNull('image')->get(['id', 'product_id', 'image']) as $variation) {
            $known = DB::table('product_images')->where('product_variation_id', $variation->id)->where('path', $variation->image)->exists();

            if ($known) {
                continue;
            }

            DB::table('product_images')->insert([
                'product_id' => $variation->product_id,
                'product_variation_id' => $variation->id,
                'path' => $variation->image,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('product_images')->whereNotNull('product_variation_id')->delete();

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variation_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('variation_image');
        });
    }
};
