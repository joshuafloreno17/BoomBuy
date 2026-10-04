<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Automatic chat messages:
//  - messages.kind: 'text' (typed by a person), 'auto_reply' (the shop's
//    "we'll reply soon"), 'order_update' (order placed / ready / out for
//    delivery / delivered / cancelled), with order_id for the order card.
//  - seller_applications.auto_reply_*: the shop's own auto-reply (on by default).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('kind', 20)->default('text')->after('message');
            $table->foreignId('order_id')->nullable()->after('product_id')->constrained('orders')->nullOnDelete();
        });

        Schema::table('seller_applications', function (Blueprint $table) {
            $table->boolean('auto_reply_enabled')->default(true)->after('shop_description');
            $table->string('auto_reply_message', 300)->nullable()->after('auto_reply_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropColumn(['auto_reply_enabled', 'auto_reply_message']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('kind');
        });
    }
};
