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
    Schema::table('orders', function (Blueprint $table) {
        // ১. user_id কে nullable করা (যাতে লগইন ছাড়াও অর্ডার সেভ হয়)
        $table->bigInteger('user_id')->unsigned()->nullable()->change();

        // ২. কাস্টমারের তথ্য রাখার জন্য নতুন কলাম যোগ করা
        $table->string('name')->nullable()->after('user_id');
        $table->string('phone')->nullable()->after('name');
        $table->text('address')->nullable()->after('phone');
        $table->string('area')->nullable()->after('address');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
