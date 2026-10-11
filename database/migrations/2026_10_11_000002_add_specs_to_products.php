<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Device details a seller fills in for a phone, tablet or laptop (RAM,
 * screen, battery…), shown as "Specifications" on the product page.
 * Storage is not here — it is an option buyers pick (Color × Storage).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('specs')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specs');
        });
    }
};
