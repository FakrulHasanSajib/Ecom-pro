<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            // Foreign Key: Products টেবিল অবশ্যই আগে তৈরি হতে হবে
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->string('size')->nullable();   // e.g. S, M, L, XL
            $table->string('color')->nullable();  // e.g. Red, Blue, #FFFFFF

            // ভেরিয়েশনের নিজস্ব ছবি থাকতে পারে (অপশনাল)
            $table->string('variant_image')->nullable();

            // ভেরিয়েশন অনুযায়ী আলাদা দাম (না দিলে মেইন প্রোডাক্টের দাম ধরবে)
            $table->decimal('price', 10, 2)->default(0);

            // ভেরিয়েশনের নিজস্ব স্টক ও SKU
            $table->integer('stock_qty')->default(0);
            $table->string('sku')->unique()->nullable(); // SKU ইউনিক হওয়া ভালো

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
