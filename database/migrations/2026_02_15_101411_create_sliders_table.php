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
        Schema::create('sliders', function (Blueprint $table) {
   $table->id();
        $table->string('title')->nullable(); // স্লাইডারের ওপর টেক্সট
        $table->string('short_description')->nullable();
        $table->string('image'); // ইমেজের পাথ
        $table->string('link')->nullable(); // ইমেজে ক্লিক করলে কোথায় যাবে
        $table->integer('serial')->default(0); // সিরিয়াল মেইনটেইন করার জন্য
        $table->boolean('status')->default(true); // Active/Inactive
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
