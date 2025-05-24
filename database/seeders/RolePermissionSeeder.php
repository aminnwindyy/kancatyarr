<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ریست کردن کش
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ایجاد مجوزها
        $permissions = [
            // مجوزهای مدیریت کاربران
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // مجوزهای مدیریت درخواست‌ها
            'requests.view',
            'requests.process',
            'requests.delete',

            // مجوزهای مدیریت محصولات
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // مجوزهای مدیریت تنظیمات
            'settings.view',
            'settings.edit',

            // سایر مجوزهای سیستم
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ایجاد نقش مدیر کل (Super Admin)
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // ایجاد نقش ادمین معمولی با دسترسی محدود به درخواست‌ها
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'requests.view',
            'requests.process'
        ]);
    }
}
