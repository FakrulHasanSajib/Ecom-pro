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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Core Review Data
            $table->unsignedTinyInteger('rating'); // 1 to 5 (Integer is enough)
            $table->text('comment')->nullable(); // Main review text

            // Media (Stores file paths)
            $table->string('review_image')->nullable(); // Correct type is string
            $table->string('review_video')->nullable(); // Correct type is string

            // Meta & Status
            $table->json('meta_data')->nullable(); // For pros/cons
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false); // Verified Purchase Check

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
