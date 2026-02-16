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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
    $table->string('code')->unique();
    $table->enum('type', ['fixed', 'percent']);
    $table->decimal('value', 10, 2); // কত টাকা বা পার্সেন্ট কমবে
    $table->decimal('min_spend', 10, 2)->nullable(); // সর্বনিম্ন কত টাকার অর্ডার হতে হবে
    $table->timestamp('expires_at')->nullable();
    $table->integer('usage_limit')->nullable(); // মোট কতবার ব্যবহার করা যাবে
    $table->integer('used_count')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
