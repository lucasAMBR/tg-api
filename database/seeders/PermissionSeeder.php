<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = ([

            ['name' => 'dev_profile.view', 'guard_name' => 'api'],
            ['name' => 'dev_profile.create', 'guard_name' => 'api'],
            ['name' => 'dev_profile.update', 'guard_name' => 'api'],
            ['name' => 'dev_profile.delete', 'guard_name' => 'api'],

            ['name' => 'employment_history.view', 'guard_name' => 'api'],
            ['name' => 'employment_history.create', 'guard_name' => 'api'],
            ['name' => 'employment_history.update', 'guard_name' => 'api'],
            ['name' => 'employment_history.delete', 'guard_name' => 'api'],

            ['name' => 'project_history.view', 'guard_name' => 'api'],
            ['name' => 'project_history.create', 'guard_name' => 'api'],
            ['name' => 'project_history.update', 'guard_name' => 'api'],
            ['name' => 'project_history.delete', 'guard_name' => 'api'],

            ['name' => 'company_profile.view', 'guard_name' => 'api'],
            ['name' => 'company_profile.create', 'guard_name' => 'api'],
            ['name' => 'company_profile.update', 'guard_name' => 'api'],
            ['name' => 'company_profile.delete', 'guard_name' => 'api'],

            ['name' => 'client_profile.view', 'guard_name' => 'api'],
            ['name' => 'client_profile.create', 'guard_name' => 'api'],
            ['name' => 'client_profile.update', 'guard_name' => 'api'],
            ['name' => 'client_profile.delete', 'guard_name' => 'api'],
        ]);

        foreach($permissions as $permission){
            Permission::create($permission);
        }
    }
}
