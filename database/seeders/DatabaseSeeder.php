<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ابتدا نقش‌ها و مجوزها را ایجاد می‌کنیم
        $this->call(RolePermissionSeeder::class);

        // سپس کاربران ادمین را ایجاد می‌کنیم
        $this->call(AdminUserSeeder::class);
    }
}
