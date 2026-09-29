<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer address book: several delivery addresses per buyer, one of them the
 * default. Checkout lets the buyer pick one instead of retyping it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // e.g. "Home", "Work", "Mom's house"
            $table->string('label', 40)->nullable();

            $table->string('phone', 20);
            $table->string('address', 255);

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_addresses');
    }
};
