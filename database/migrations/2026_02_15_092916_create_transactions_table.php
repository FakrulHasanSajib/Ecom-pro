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
        Schema::create('transactions', function (Blueprint $table) {
           $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('order_id')->constrained();
    $table->string('transaction_id')->unique(); // SSLCommerz এর tran_id
    $table->string('gateway'); // sslcommerz, stripe, cod
    $table->decimal('amount', 12, 2);
    $table->string('status'); // pending, success, failed, cancelled
    $table->json('payment_details')->nullable(); // গেটওয়ে থেকে আসা পুরো রেসপন্স JSON আকারে থাকবে
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
