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
            'admin@example.com',
            'admin'
        );

        $this->createUserIfNotExists(
            'dev@example.com',
            'dev'
        );

        $this->createUserIfNotExists(
            'company@example.com',
            'company'
        );

        $this->createUserIfNotExists(
            'client@example.com',
            'client'
        );
        
        $this->call([
            DevPreferencesSeeder::class
        ]);
    }

    private function createUserIfNotExists(
        string $email,
        string $role
    ): void {

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'password' => Hash::make('user1234!')
            ]
        );

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    private function createAdminIfNotExists(
        string $email,
        string $role
    ): void {

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'admin_active_profile' => 'dev',
                'password' => Hash::make('user1234!')
            ]
        );

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
