<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $groups = [
            'dashboard' => ['dashboard.view'],
            'users' => [
                'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign-roles',
            ],
            'roles' => [
                'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.assign-permissions',
            ],
            'settings' => [
                'settings.view', 'settings.update', 'settings.run-commands',
            ],
            'media' => [
                'media.view', 'media.upload', 'media.delete',
            ],
            'profile' => [
                'profile.update',
            ],
            'activity' => [
                'activity.dashboard', 'activity.feed', 'activity.delete', 'activity.my',
            ],
            'backup' => [
                'backups.view', 'backups.create', 'backups.download', 'backups.delete', 'backups.manage-schedules', 'backups.cleanup',
            ],
            'translate' => [
                'translations.view', 'translations.create', 'translations.update', 'translations.delete', 'translations.scan', 'translations.import', 'translations.export', 'translations.ai-translate',
            ],
            'pages' => [
                'pages.view', 'pages.create', 'pages.edit', 'pages.delete',
            ],
            'products' => [
                'products.view', 'products.create', 'products.edit', 'products.delete',
            ],
            'categories' => [
                'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            ],
            'brands' => [
                'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
            ],
            'units' => [
                'units.view', 'units.create', 'units.edit', 'units.delete',
            ],
            'inventory' => [
                'inventory.view', 'inventory.adjust', 'inventory.movements', 'inventory.batches',
            ],
            'suppliers' => [
                'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            ],
            'purchases' => [
                'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete', 'purchases.payment',
            ],
            'purchase_returns' => [
                'purchase_returns.view', 'purchase_returns.create',
            ],
            'customers' => [
                'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            ],
            'sales' => [
                'pos.access', 'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.void',
            ],
            'orders' => [
                'orders.view', 'orders.manage', 'orders.cancel', 'orders.return', 'orders.print',
            ],
        ];

        // Create permissions
        foreach ($groups as $items) {
            foreach ($items as $name) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
            }
        }

        // Create roles
        $super = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard]);
        $provider = Role::firstOrCreate(['name' => 'provider', 'guard_name' => $guard]);
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        // Assign permissions
        $allPerms = Permission::where('guard_name', $guard)->get();
        $super->syncPermissions($allPerms);

        $adminPerms = Permission::whereIn('name', [
            'dashboard.view',
            'users.view', 'users.create', 'users.update', 'users.assign-roles',
            'roles.view', 'roles.create', 'roles.update', 'roles.assign-permissions',
            'settings.view', 'settings.update',
            'media.view', 'media.upload',
            'profile.update',
            'activity.dashboard', 'activity.feed', 'activity.delete',
            'pages.view', 'pages.create', 'pages.edit',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
            'units.view', 'units.create', 'units.edit', 'units.delete',
            'inventory.view', 'inventory.adjust', 'inventory.movements', 'inventory.batches',
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.payment',
            'purchase_returns.view', 'purchase_returns.create',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'pos.access', 'sales.view', 'sales.create', 'sales.edit', 'sales.void',
            'orders.view', 'orders.manage', 'orders.cancel', 'orders.return', 'orders.print',
        ])->get();
        $admin->syncPermissions($adminPerms);

        $userPerms = Permission::whereIn('name', [
            'dashboard.view',
            'profile.update',
            'activity.my',
        ])->get();
        $user->syncPermissions($userPerms);
    }
}
