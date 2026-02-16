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
        Schema::table('products', function (Blueprint $table) {
            $table->json('colors')->nullable(); // কালার অ্যারে
        $table->json('sizes')->nullable();  // সাইজ অ্যারে
        $table->json('tags')->nullable();   // ট্যাগ অ্যারে
        $table->foreignId('brand_id')->nullable()->constrained();
        $table->string('unit')->nullable()->after('name'); // unit
        $table->string('video_link')->nullable()->after('description');
        $table->decimal('purchase_price', 12, 2)->default(0)->after('base_price');
        $table->decimal('wholesale_price', 12, 2)->default(0)->after('purchase_price');
        $table->decimal('reseller_price', 12, 2)->default(0)->after('wholesale_price');

        // Shipping
        $table->boolean('free_shipping')->default(false);
        $table->decimal('shipping_inside_dhaka', 8, 2)->nullable();
        $table->decimal('shipping_outside_dhaka', 8, 2)->nullable();
        $table->json('testimonials')->nullable()->after('description');
        $table->string('slug')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
