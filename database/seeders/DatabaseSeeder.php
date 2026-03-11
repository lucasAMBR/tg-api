<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
            RoleSeeder::class,
            LanguageSeeder::class,
            SoftSkillSeeder::class,
            SoftSkillLevelResponseSeeder::class
        ]);

        $this->createAdminIfNotExists(
            'Admin User',
            'admin@example.com',
            '+5535962644988',
            'admin'
        );

        $this->createUserIfNotExists(
            'Dev User',
            'dev@example.com',
            '+5535962644987',
            'dev'
        );

        $this->createUserIfNotExists(
            'Company User',
            'company@example.com',
            '+5535962644989',
            'company'
        );

        $this->createUserIfNotExists(
            'Client User',
            'client@example.com',
            '+5535962644985',
            'client'
        );
    }

    private function createUserIfNotExists(
        string $name,
        string $email,
        string $phone,
        string $role
    ): void {

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'phone'    => $phone,
                'password' => Hash::make('user1234!')
            ]
        );

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    private function createAdminIfNotExists(
        string $name,
        string $email,
        string $phone,
        string $role
    ): void {

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'phone'    => $phone,
                'admin_active_profile' => 'dev',
                'password' => Hash::make('user1234!')
            ]
        );

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
