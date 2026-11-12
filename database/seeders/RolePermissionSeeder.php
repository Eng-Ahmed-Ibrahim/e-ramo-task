<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {

        $modules = [
            'branches',
            'categories',
            'products',
            'inventories',
            'orders',
            'payments',
            'customers',
        ];

        $permissions = [];

        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $permissions[] = "{$action}-{$module}";
            }
        }

        $permissions = array_merge($permissions, [
            'manage-reservations',
            'manage-inventory',
            'view-inventory-movements',
            'view-order-history',
            'cancel-orders',
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $adminRole->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

    }
}
