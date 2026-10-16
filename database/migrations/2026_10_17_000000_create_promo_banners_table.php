<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promo banners the admin puts at the top of the landing page and the buyer
     * home: the big rotating banner ("main") and the two small tiles beside it.
     */
    public function up(): void
    {
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20)->default('main'); // main | side_top | side_bottom
            $table->string('eyebrow', 60)->nullable();         // small line above the title, e.g. "Nov 1 – 11"
            $table->string('title', 80);
            $table->string('subtitle', 200)->nullable();
            $table->string('big_text', 12)->nullable();        // e.g. "11.11" or "-40%"
            $table->string('button_label', 30)->nullable();
            $table->string('link_type', 20)->default('products'); // products | deals | category | product | shop
            $table->string('link_value', 60)->nullable();      // category slug, product id or seller id
            $table->string('style', 20)->default('violet');    // violet | lavender | plum | light
            $table->string('image')->nullable();               // public disk path
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['placement', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_banners');
    }
};
