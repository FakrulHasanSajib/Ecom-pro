<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('order_statuses', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique(); // স্ট্যাটাসের নাম (যেমন: Pending, Shipped)
        $table->string('color_class')->default('text-slate-800'); // কালারের জন্য (ঐচ্ছিক)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
