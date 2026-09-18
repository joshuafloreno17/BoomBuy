<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();

            // Seller account
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Personal / business information
            $table->string('full_name');
            $table->string('phone');
            $table->text('address');

            // Uploaded requirements
            $table->string('national_id')->nullable();
            $table->string('business_permit')->nullable();

            // Application status
            $table->enum('status', [
                'Pending Verification',
                'Approved',
                'Rejected'
            ])->default('Pending Verification');

            // Admin review
            $table->text('admin_remarks')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();

            $table->timestamps();

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_applications');
    }
};
