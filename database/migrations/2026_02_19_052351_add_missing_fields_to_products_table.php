<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Brand & Unit
            $table->unsignedBigInteger('brand_id')->nullable()->after('category_id');
            $table->string('unit')->default('Piece')->after('sku');

            // Prices
            $table->decimal('purchase_price', 12, 2)->default(0)->after('base_price');
            $table->decimal('wholesale_price', 12, 2)->default(0)->after('purchase_price');
            $table->decimal('reseller_price', 12, 2)->default(0)->after('wholesale_price');
            $table->decimal('offer_price', 12, 2)->nullable()->after('sale_price');

            // Media & Links
            $table->string('thumbnail')->nullable()->after('description');
            $table->text('images')->nullable()->after('thumbnail');
            $table->string('video_link')->nullable()->after('images');

            // Variations
            $table->boolean('enable_variants')->default(0)->after('total_stock');
            $table->text('colors')->nullable()->after('enable_variants');
            $table->text('sizes')->nullable()->after('colors');

            // Shipping
            $table->boolean('free_shipping')->default(0)->after('sizes');
            $table->decimal('shipping_inside_dhaka', 8, 2)->default(80)->after('free_shipping');
            $table->decimal('shipping_outside_dhaka', 8, 2)->default(150)->after('shipping_inside_dhaka');

            // Extras
            $table->longText('testimonials')->nullable()->after('tags');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand_id', 'unit',
                'purchase_price', 'wholesale_price', 'reseller_price', 'offer_price',
                'thumbnail', 'images', 'video_link',
                'enable_variants', 'colors', 'sizes',
                'free_shipping', 'shipping_inside_dhaka', 'shipping_outside_dhaka',
                'testimonials'
            ]);
        });
    }
};

