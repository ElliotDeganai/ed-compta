<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'view dashboard',
        'manage transactions',
        'manage recurring',
        'manage categories',
        'manage settings',
        'manage users',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(self::PERMISSIONS);

        $viewer = Role::findOrCreate('viewer', 'web');
        $viewer->syncPermissions(['view dashboard']);
    }
}
