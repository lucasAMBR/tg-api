<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class
        ]);

        $user = User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'phone'    => '+5535962644988',
            'cpf'      => '93709815002',
            'password' => 'user1234!'
        ]);

        $user->roles()->attach(Role::whereIn('name', ['admin', 'user'])->pluck('id'));

        $secondUser = User::create([
            'name'     => 'Test User 2',
            'email'    => 'second_test@example.com',
            'phone'    => '+5535962644989',
            'cpf'      => '93709815003',
            'password' => 'user1234!'
        ]);

        $secondUser->roles()->attach(Role::whereIn('name', ['user'])->pluck('id'));
    }
}
