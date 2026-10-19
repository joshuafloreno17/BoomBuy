<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* "Remind me" on an upcoming flash sale (Buyer B14). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['flash_sale_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_reminders');
    }
};
