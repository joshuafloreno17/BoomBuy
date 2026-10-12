<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logistics company registration details: SEC registration number, TIN,
 * region, postal code, the contact person's position, the regions served
 * and two more documents (SEC Certificate, BIR Form 2303). All nullable —
 * companies that registered before these existed keep working as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_applications', function (Blueprint $table) {
            $table->string('sec_registration_no', 11)->nullable()->index()->after('business_name');
            $table->string('tin', 15)->nullable()->index()->after('sec_registration_no');
            $table->string('position')->nullable()->after('full_name');
            $table->string('region')->nullable()->after('address');
            $table->string('postal_code', 4)->nullable()->after('region');
            $table->text('service_areas')->nullable()->after('postal_code');
            $table->string('sec_certificate')->nullable()->after('business_permit');
            $table->string('bir_2303')->nullable()->after('sec_certificate');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_applications', function (Blueprint $table) {
            $table->dropIndex(['sec_registration_no']);
            $table->dropIndex(['tin']);
            $table->dropColumn(['sec_registration_no', 'tin', 'position', 'region', 'postal_code', 'service_areas', 'sec_certificate', 'bir_2303']);
        });
    }
};
