<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The test mailbox (/mailbox): emails to the test sheets' @example.com
     * addresses are kept here instead of being sent, so testers can read
     * the OTP codes and activation links.
     */
    public function up(): void
    {
        Schema::create('test_mails', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('subject');
            $table->longText('html')->nullable();
            $table->text('text')->nullable();
            $table->timestamps();

            $table->index('to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_mails');
    }
};
