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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            // User who filed the complaint (buyer, seller, or rider)
            $table->foreignId('complainant_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('complainant_role');

            // Who the complaint is against (optional — not every complaint
            // names another user, e.g. a general platform issue)
            $table->foreignId('against_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Related order, if any
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->string('subject');
            $table->text('description');
            $table->string('evidence')->nullable();

            // Pending, Under Review, Resolved, Dismissed
            $table->string('status')->default('Pending');

            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
