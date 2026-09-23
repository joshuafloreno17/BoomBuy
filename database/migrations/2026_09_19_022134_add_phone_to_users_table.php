<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The phone column already exists in the users table.
        // No database change is needed.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not remove the existing phone column.
    }
};