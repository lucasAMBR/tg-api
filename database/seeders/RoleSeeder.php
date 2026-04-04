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
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $adminRole->syncPermissions(Permission::all());

        $clientRole = Role::firstOrCreate([
            'name' => 'client',
            'guard_name' => 'api'
        ]);

        $clientRole->syncPermissions([
            'client_profile.view',
            'client_profile.create',
            'client_profile.update',
            'client_profile.delete',
            'dev_profile.view',
            'address.view',
            'address.create',
            'address.update',
            'address.delete'
        ]);

        $companyRole = Role::firstOrCreate([
            'name' => 'company',
            'guard_name' => 'api'
        ]);

        $companyRole->syncPermissions([
            'company_profile.view',
            'company_profile.create',
            'company_profile.update',
            'company_profile.delete',
            'dev_profile.view',
            'address.view',
            'address.create',
            'address.update',
            'address.delete',
            'language.create',
            'company_project.view',
            'company_project.create',
            'company_project.update',
            'company_project.delete',
            'company_soft_skill.create',
            'company_soft_skill.update',
            'company_soft_skill.view',
            'company_soft_skill.delete',
            'company_job_vacancy.create'
        ]);

        $devRole = Role::firstOrCreate([
            'name' => 'dev',
            'guard_name' => 'api'
        ]);

        $devRole->syncPermissions([
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
            'address.view',
            'address.create',
            'address.update',
            'address.delete',
            'language.create',
            'academic_background.create',
            'academic_background.update',
            'academic_background.delete',
            'additional_course.create',
            'additional_course.update',
            'additional_course.delete',
            'hard_skill.create',
            'hard_skill.update',
            'hard_skill.delete',
            'dev_soft_skill.create',
            'dev_soft_skill.update',
        ]);
    }
}
