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
        Schema::create('orders', function (Blueprint $table) {
           $table->id();
    $table->uuid('uuid')->unique();
    $table->string('order_number')->unique();
    $table->foreignId('user_id')->constrained();
    $table->decimal('sub_total', 12, 2);
    $table->decimal('shipping_charge', 12, 2)->default(0);
    $table->decimal('grand_total', 12, 2);
    $table->string('payment_method'); // SSLCommerz, Stripe, COD
    $table->string('payment_status')->default('pending');
    $table->string('order_status')->default('pending');

    // Advanced Tracking
    $table->json('tracking_info')->nullable(); // Store fbp, fbc, gclid, ttclid
    $table->string('utm_source')->nullable();
    $table->string('ip_address')->nullable();
    $table->text('user_agent')->nullable();

    $table->softDeletes();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
