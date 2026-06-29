<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionAndAdminSeeder extends Seeder
{
    public const ADMIN_GUARD = 'admin';

    public const PERMISSIONS = [
        'products.view',
        'products.create',
        'products.update',
        'products.delete',
        'orders.view',
        'transfers.view',
        'transfers.approve',
        'transfers.reject',
        'transfers.review',
        'users.view',
        'admins.view',
        'admins.create',
        'admins.update',
        'admins.delete',
        'faqs.view',
        'faqs.create',
        'faqs.update',
        'faqs.delete',
        'faqs.manage',
        'settings.view',
        'settings.update',
        'settings.manage',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'roles.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => self::ADMIN_GUARD,
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => self::ADMIN_GUARD,
        ]);

        $role->syncPermissions(self::PERMISSIONS);

        $admin = Admin::updateOrCreate(
            ['email' => 'admin@drasa.test'],
            [
                'name' => 'Main Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $admin->assignRole($role);
    }
}
