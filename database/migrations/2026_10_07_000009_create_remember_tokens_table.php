<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One "Remember me" token per browser. users.remember_token held one per
 * account, so ticking "Remember me" on a phone signed the laptop out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remember_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Browsers remembered before this keep working.
        DB::table('users')->whereNotNull('remember_token')->get(['id', 'remember_token'])->each(function ($user) {
            DB::table('remember_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => $user->remember_token,
                'last_used_at' => now(),
                'created_at' => now(),
            ]);
        });

        DB::table('users')->update(['remember_token' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('remember_tokens');
    }
};
