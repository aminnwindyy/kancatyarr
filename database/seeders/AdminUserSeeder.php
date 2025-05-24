<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // ایجاد کاربر مدیر کل
        $superAdmin = User::create([
            'first_name' => 'مدیر',
            'last_name' => 'کل',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // اختصاص نقش مدیر کل به این کاربر
        $superAdmin->assignRole('super-admin');

        // ایجاد کاربر ادمین با دسترسی محدود
        $admin = User::create([
            'first_name' => 'ادمین',
            'last_name' => 'درخواست‌ها',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // اختصاص نقش ادمین معمولی به این کاربر
        $admin->assignRole('admin');
    }
}
