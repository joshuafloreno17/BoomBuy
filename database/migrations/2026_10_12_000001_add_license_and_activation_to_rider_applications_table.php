<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riders: driver's license number and expiry, and riders a logistics company
 * adds itself (created_by) who set their own password through an emailed
 * activation link. Until then the account is "Pending Activation".
 * All new columns are nullable — existing riders keep working as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_applications', function (Blueprint $table) {
            $table->string('drivers_license_no', 13)->nullable()->index()->after('plate_number');
            $table->date('license_expiry')->nullable()->after('drivers_license_no');
            $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('activation_token', 64)->nullable()->after('status');
            $table->timestamp('activation_expires_at')->nullable()->after('activation_token');
            $table->timestamp('activated_at')->nullable()->after('activation_expires_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['Active', 'Suspended', 'Deactivated', 'Pending Activation'])->default('Active')->change();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('users')->where('status', 'Pending Activation')->update(['status' => 'Deactivated']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['Active', 'Suspended', 'Deactivated'])->default('Active')->change();
        });

        Schema::table('rider_applications', function (Blueprint $table) {
            $table->dropIndex(['drivers_license_no']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['drivers_license_no', 'license_expiry', 'activation_token', 'activation_expires_at', 'activated_at']);
        });
    }
};
