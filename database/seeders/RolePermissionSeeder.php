<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ১. রোল তৈরি করা
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);

        // ২. কিছু বেসিক পারমিশন তৈরি করা
        $permissions = [
            'manage categories',
            'manage products',
            'manage orders',
            'view analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ৩. এডমিনকে সব পারমিশন দেওয়া
        $adminRole->givePermissionTo(Permission::all());

        // ৪. একজন ডিফল্ট সুপার এডমিন ইউজার তৈরি করা (আপনার জন্য)
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);

        $admin->assignRole($adminRole);

        // ৫. একজন টেস্ট কাস্টমার ইউজার তৈরি করা
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'password' => Hash::make('password123'),
        ]);

        $customer->assignRole($customerRole);
    }
}
