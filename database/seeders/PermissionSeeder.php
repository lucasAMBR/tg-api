<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = ([
            ['name' => 'post.create'],
            ['name' => 'post.update.own'],
            ['name' => 'post.update.any'],
            ['name' => 'post.delete.own'],
            ['name' => 'post.delete.any'],
            ['name' => 'post.report.any'],
        ]);

        foreach($permissions as $permission){
            Permission::create($permission);
        }
    }
}
