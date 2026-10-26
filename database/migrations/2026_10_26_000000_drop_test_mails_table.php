<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The test sheets' mailbox (/mailbox) is gone now that the other
     * groups' check is done.
     */
    public function up(): void
    {
        Schema::dropIfExists('test_mails');
    }

    public function down(): void
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
};
