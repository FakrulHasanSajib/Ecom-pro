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
        // যদি কলামগুলো আগে থেকেই না থাকে, তবে এড করবে
        if (!Schema::hasColumn('products', 'meta_title')) {
            $table->string('meta_title')->nullable();
        }
        if (!Schema::hasColumn('products', 'meta_description')) {
            $table->text('meta_description')->nullable();
        }
        if (!Schema::hasColumn('products', 'tags')) {
            $table->text('tags')->nullable(); // ট্যাগ কমা সেপারেটেড স্ট্রিং হিসেবে থাকবে
        }
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
