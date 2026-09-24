<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_initial', 5)->nullable()->after('first_name');
            $table->string('sex', 10)->nullable()->after('middle_initial');
            $table->date('birthdate')->nullable()->after('sex');
            $table->unsignedTinyInteger('age')->nullable()->after('birthdate');
            $table->string('id_photo')->nullable()->after('profile_photo');
            $table->string('province')->nullable()->after('address');
            $table->string('city_municipality')->nullable()->after('province');
            $table->string('barangay')->nullable()->after('city_municipality');
            $table->string('street_address')->nullable()->after('barangay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_name',
                'first_name',
                'middle_initial',
                'sex',
                'birthdate',
                'age',
                'id_photo',
                'province',
                'city_municipality',
                'barangay',
                'street_address',
            ]);
        });
    }
};
