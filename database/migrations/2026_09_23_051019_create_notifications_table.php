<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // User who will receive the notification
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Notification content
            $table->string('title');
            $table->text('message');

            // Optional category
            // Examples: order, application, delivery, return_refund, system
            $table->string('type')->nullable();

            // Optional related record
            $table->unsignedBigInteger('reference_id')->nullable();

            // Whether the user has already opened/read it
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};