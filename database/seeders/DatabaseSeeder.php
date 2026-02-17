<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'phone'    => '+5535962644988',
            'password' => 'user1234!'
        ]);

        $user->assignRole('admin');

        $devUser = User::create([
            'name'     => 'Dev User',
            'email'    => 'dev@example.com',
            'phone'    => '+5535962644987',
            'password' => 'user1234!'
        ]);

        $devUser->assignRole('dev');

        $companyUser = User::create([
            'name'     => 'Company User',
            'email'    => 'company@example.com',
            'phone'    => '+5535962644989',
            'password' => 'user1234!'
        ]);

        $companyUser->assignRole('company');

        $clientUser = User::create([
            'name'     => 'Client User',
            'email'    => 'client@example.com',
            'phone'    => '+5535962644985',
            'password' => 'user1234!'
        ]);

        $clientUser->assignRole('client');
    }
}
