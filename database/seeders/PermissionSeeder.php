<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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

            ['name' => 'academic_background.create', 'guard_name' => 'api'],
            ['name' => 'academic_background.update', 'guard_name' => 'api'],
            ['name' => 'academic_background.delete', 'guard_name' => 'api'],

            ['name' => 'additional_course.create', 'guard_name' => 'api'],
            ['name' => 'additional_course.update', 'guard_name' => 'api'],
            ['name' => 'additional_course.delete', 'guard_name' => 'api'],

            ['name' => 'hard_skill.create', 'guard_name' => 'api'],
            ['name' => 'hard_skill.update', 'guard_name' => 'api'],
            ['name' => 'hard_skill.delete', 'guard_name' => 'api'],

            ['name' => 'dev_soft_skill.create', 'guard_name' => 'api'],
            ['name' => 'dev_soft_skill.update', 'guard_name' => 'api'],

            ['name' => 'company_soft_skill.sync', 'guard_name' => 'api'],
            ['name' => 'company_soft_skill.view', 'guard_name' => 'api'],

            ['name' => 'company_job_vacancy.create', 'guard_name' => 'api'],
            ['name' => 'company_job_vacancy.view', 'guard_name' => 'api'],
            ['name' => 'company_job_vacancy.delete', 'guard_name' => 'api'],
            ['name' => 'company_job_vacancy.update', 'guard_name' => 'api'],

            ['name' => 'company_profile.view', 'guard_name' => 'api'],
            ['name' => 'company_profile.create', 'guard_name' => 'api'],
            ['name' => 'company_profile.update', 'guard_name' => 'api'],
            ['name' => 'company_profile.delete', 'guard_name' => 'api'],

            ['name' => 'company_stack.sync', 'guard_name' => 'api'],

            ['name' => 'company_project.view', 'guard_name' => 'api'],
            ['name' => 'company_project.create', 'guard_name' => 'api'],
            ['name' => 'company_project.update', 'guard_name' => 'api'],
            ['name' => 'company_project.delete', 'guard_name' => 'api'],

            ['name' => 'client_profile.view', 'guard_name' => 'api'],
            ['name' => 'client_profile.create', 'guard_name' => 'api'],
            ['name' => 'client_profile.update', 'guard_name' => 'api'],
            ['name' => 'client_profile.delete', 'guard_name' => 'api'],

            ['name' => 'address.view', 'guard_name' => 'api'],
            ['name' => 'address.create', 'guard_name' => 'api'],
            ['name' => 'address.update', 'guard_name' => 'api'],
            ['name' => 'address.delete', 'guard_name' => 'api'],

            ['name' => 'language.create', 'guard_name' => 'api'],
            ['name' => 'language.update', 'guard_name' => 'api'],
            ['name' => 'language.delete', 'guard_name' => 'api'],
            ['name' => 'language.approve', 'guard_name' => 'api']
        ]);

        foreach($permissions as $permission){
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name']
            ]);
        }
    }
}
