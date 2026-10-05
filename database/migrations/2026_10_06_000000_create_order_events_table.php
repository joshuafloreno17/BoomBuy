<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Every step an order takes, with when and where — the buyer's tracking timeline. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status', 40);
            $table->string('title', 120);
            $table->string('detail', 255)->nullable();
            $table->foreignId('center_id')->nullable()->constrained('sorting_centers')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
    }
};
