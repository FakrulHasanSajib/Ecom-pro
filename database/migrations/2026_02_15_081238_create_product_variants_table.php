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
        $table->foreignId('product_id')->constrained()->onDelete('cascade');

        $table->string('size')->nullable();   // e.g. S, M, L, XL, 42, 44
        $table->string('color')->nullable();  // e.g. Red, Blue, #FFFFFF

        // ভেরিয়েশন অনুযায়ী আলাদা দাম হতে পারে (না হলে মেইন প্রোডাক্টের দাম থাকবে)
        $table->decimal('price', 10, 2)->default(0);

        // ভেরিয়েশনের নিজস্ব স্টক
        $table->integer('stock_qty')->default(0);

        // ভেরিয়েশনের নিজস্ব SKU (e.g. SHIRT-RED-L)
        $table->string('sku')->nullable();

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
