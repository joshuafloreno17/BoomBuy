<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.phone and users.address were originally added by hand in
     * phpMyAdmin (the migration right before this one is empty), so a fresh
     * database never got them — registration then crashed, and the later
     * "->after('address')" migration failed on MySQL. Only adds what's
     * missing, so it's a no-op on databases that already have them.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Left in place — existing databases had these columns before this
        // migration existed, so rolling it back must not drop them.
    }
};
