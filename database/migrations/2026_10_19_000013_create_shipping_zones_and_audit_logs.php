<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Logistics → Zones & Rates (L7, Buyer B8): delivery zones (lists of
 * provinces) and a first-kg / each-extra-kg rate per origin → destination
 * zone. While no zone exists, the admin's Delivery Fee settings are used as
 * before. Audit logs record who changed what (rates, status overrides).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->json('provinces');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->foreignId('to_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->decimal('first_kg', 10, 2);
            $table->decimal('per_kg', 10, 2);
            $table->timestamps();
            $table->unique(['from_zone_id', 'to_zone_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
    }
};
