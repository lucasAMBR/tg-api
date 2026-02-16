<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::create(['name' => 'admin']);

        $adminRole->givePermissionTo(Permission::all());

        $clientRole = Role::create(['name' => 'client']);

        $clientRole->givePermissionTo(Permission::whereIn('name', [
            'client_profile.view',
            'client_profile.create',
            'client_profile.update',
            'client_profile.delete',
            'dev_profile.view'
        ]));

        $devRole = Role::create(['name' => 'dev']);

        $companyRole = Role::create(['name' => 'company']);

        $companyRole->givePermissionTo(Permission::whereIn('name', [
            'company_profile.view',
            'company_profile.create',
            'company_profile.update',
            'company_profile.delete',
            'dev_profile.view'
        ]));

        $devRole->givePermissionTo(Permission::whereIn('name', [
            'dev_profile.view',
            'dev_profile.create',
            'dev_profile.update',
            'dev_profile.delete',
            'employment_history.view',
            'employment_history.create',
            'employment_history.update',
            'employment_history.delete',
            'project_history.view',
            'project_history.create',
            'project_history.update',
            'project_history.delete',
            'client_profile.view',
            'company_profile.view',
        ]));
    }
}
