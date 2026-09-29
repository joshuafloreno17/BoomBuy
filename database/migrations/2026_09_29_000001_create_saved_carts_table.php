<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A buyer's cart, kept with their account instead of only in the session —
 * so it survives the session expiring ("remember me" starts a fresh one),
 * logging out and back in, or switching devices. See PersistBuyerCart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_carts', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->primary()
                ->constrained('users')
                ->cascadeOnDelete();

            // Same shape as session('cart'): {"productId:variationId": quantity}
            $table->json('items');

            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_carts');
    }
};
