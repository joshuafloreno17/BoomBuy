<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_applications', function (Blueprint $table) {
            $table->id();

            // Rider account
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Personal information
            $table->string('full_name');
            $table->string('phone');
            $table->text('address');

            // Vehicle information
            $table->string('vehicle_type');
            $table->string('vehicle_model');
            $table->string('plate_number');

            // Uploaded requirements
            $table->string('national_id')->nullable();
            $table->string('drivers_license')->nullable();
            $table->string('profile_selfie')->nullable();
            $table->string('proof_of_address')->nullable();
            $table->string('or_cr')->nullable();

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
        Schema::dropIfExists('rider_applications');
    }
};