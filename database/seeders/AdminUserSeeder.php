<?php

namespace Database\Factories;
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // আগের কোনো একই ইমেলের ইউজার থাকলে তা ডিলিট করে নতুন করে তৈরি করবে
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'], // ইমেল চেক করবে
            [
                'name' => 'Admin Sajib',
                'password' => Hash::make('sajib5s'), // আপনার দেওয়া পাসওয়ার্ড
                'role' => 'admin', // নিশ্চিত করুন আপনার মাইগ্রেশনে role কলাম আছে
            ]
        );
    }
}
