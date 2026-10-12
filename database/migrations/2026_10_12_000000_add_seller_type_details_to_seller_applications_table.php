<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller registration details: seller type (Individual / Sole Proprietorship /
 * Corporation) and the documents each type uploads, TIN, region and postal
 * code. All nullable — sellers who registered before these existed keep
 * working as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->string('seller_type', 30)->nullable()->after('business_category');
            $table->string('tin', 15)->nullable()->index()->after('seller_type');
            $table->string('region')->nullable()->after('address');
            $table->string('postal_code', 4)->nullable()->after('region');
            $table->string('dti_certificate')->nullable()->after('business_permit');
            $table->string('sec_certificate')->nullable()->after('dti_certificate');
            $table->string('bir_2303')->nullable()->after('sec_certificate');
        });
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropIndex(['tin']);
            $table->dropColumn(['seller_type', 'tin', 'region', 'postal_code', 'dti_certificate', 'sec_certificate', 'bir_2303']);
        });
    }
};
