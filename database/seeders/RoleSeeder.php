<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        $userRole->permissions()->attach(
            Permission::whereIn('name', ['post.create', 'post.update.own', 'post.delete.own', 'post.report.any'])->pluck('id')
        );

        $adminRole->permissions()->attach(
            Permission::whereIn('name', ['post.update.any', 'post.delete.any'])->pluck('id')
        );
    }
}
