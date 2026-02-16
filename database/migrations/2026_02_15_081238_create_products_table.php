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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
    $table->foreignId('category_id')->constrained();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('sku')->unique();
    $table->text('description')->nullable();
    $table->decimal('base_price', 12, 2);
    $table->decimal('sale_price', 12, 2)->nullable();
    $table->integer('total_stock')->default(0);
    $table->json('meta_data')->nullable(); // SEO Tags
    $table->enum('status', ['draft', 'published', 'inactive'])->default('published');
    $table->softDeletes();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
