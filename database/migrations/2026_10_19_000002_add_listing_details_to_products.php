<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product listing details from the Seller Modules sheet: brand,
     * condition, shipping weight and size, drafts (saved but never shown to
     * buyers) and soft deletes (a deleted product's name, photo and price
     * stay on old orders).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand', 60)->nullable()->after('category');
            $table->string('condition', 10)->default('New')->after('brand');
            $table->decimal('weight_kg', 8, 2)->nullable()->after('stock');
            $table->decimal('length_cm', 8, 2)->nullable()->after('weight_kg');
            $table->decimal('width_cm', 8, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 8, 2)->nullable()->after('width_cm');
            $table->boolean('is_draft')->default(false)->after('is_archived');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['brand', 'condition', 'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'is_draft']);
        });
    }
};
