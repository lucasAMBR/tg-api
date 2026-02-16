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
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'phone'    => '+5535962644988',
            'password' => 'user1234!'
        ]);

        $user->assignRole('admin');
    }
}
