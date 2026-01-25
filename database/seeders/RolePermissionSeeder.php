<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign-permissions',
            'users.assign-roles',

            // Role permissions
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign-permissions',

            // Permission permissions
            'permissions.view',

            // Activity permissions
            'activity.view',

            // Dashboard permissions
            'dashboard.view',

            // Patient permissions
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',
            'patients.restore',
            'patients.force-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'api')->get());

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'api']);
        $admin->syncPermissions(Permission::whereIn('name', [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign-roles',
            'roles.view',
            'roles.create',
            'roles.update',
            'permissions.view',
            'activity.view',
            'dashboard.view',
        ])->where('guard_name', 'api')->get());

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'api']);
        $manager->syncPermissions(Permission::whereIn('name', [
            'users.view',
            'users.update',
            'roles.view',
            'permissions.view',
            'dashboard.view',
        ])->where('guard_name', 'api')->get());

        $user = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'api']);
        $user->syncPermissions(Permission::whereIn('name', [
            'dashboard.view',
        ])->where('guard_name', 'api')->get());
    }
}
