<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        /* Create Roles */
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'User', 'guard_name' => 'web']);
        Role::create(['name' => 'Politur', 'guard_name' => 'web']);

        //Role Permission
        Permission::create([
            'name' => 'roles.index',
            'alias' => 'Roles list',
            'group' => 'Roles',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'roles.create',
            'alias' => 'Create role',
            'group' => 'Roles',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'roles.show',
            'alias' => 'Show role',
            'group' => 'Roles',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'roles.update',
            'alias' => 'Update role',
            'group' => 'Roles',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'roles.delete',
            'alias' => 'Delete role',
            'group' => 'Roles',
            'guard_name' => 'web'
        ]);

        //User Permission
        Permission::create([
            'name' => 'user.index',
            'alias' => 'User list',
            'group' => 'Users',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'user.create',
            'alias' => 'Create user',
            'group' => 'Users',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'user.show',
            'alias' => 'Show user',
            'group' => 'Users',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'user.update',
            'alias' => 'Update user',
            'group' => 'Users',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'user.delete',
            'alias' => 'Delete user',
            'group' => 'Users',
            'guard_name' => 'web'
        ]);

        /* User Logs */
        Permission::create([
            'name' => 'user.logs',
            'alias' => 'Users deleted',
            'group' => 'User logs',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'users.trashed',
            'alias' => 'Accounts deactivated',
            'group' => 'User logs',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'user.deletion.reasons',
            'alias' => 'User deletion reasons',
            'group' => 'User logs',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'google.analytics',
            'alias' => 'Google Analytics',
            'group' => 'User logs',
            'guard_name' => 'web'
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        /* Assign Permission to Role */
        $admin = Role::where('name', 'Admin')->first();

        $admin->givePermissionTo([
            'user.index',
            'user.create',
            'user.show',
            'user.update',
            'user.delete'
        ]);
    }
}
