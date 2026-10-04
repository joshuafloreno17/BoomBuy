<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A chat message can be about a product ("Chat" on a product page): the
// conversation then shows that product as a card. If the product is deleted
// later, the message stays and only the card goes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('message')->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
