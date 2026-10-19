<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reviews: up to 5 photos, the option bought, and one edit (A12).
     * Chat: an image attachment with (or instead of) the text (A16).
     */
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('review');
            $table->string('variation_label')->nullable()->after('photos');
            $table->timestamp('edited_at')->nullable()->after('variation_label');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('attachment')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('messages', fn (Blueprint $table) => $table->dropColumn('attachment'));
        Schema::table('product_reviews', fn (Blueprint $table) => $table->dropColumn(['photos', 'variation_label', 'edited_at']));
    }
};
