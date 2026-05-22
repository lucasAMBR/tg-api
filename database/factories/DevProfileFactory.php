<?php

namespace Database\Factories;

use App\Enums\SeniorityLevelEnum;
use App\Models\DevProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevProfile>
 */
class DevProfileFactory extends Factory
{
    protected $model = DevProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'bio' => fake()->paragraph(),
            'cpf' => '39053344705',
            'phone' => '+5531'.fake()->unique()->numerify('9########'),
            'birthdate' => '1990-01-15',
            'seniority_level' => SeniorityLevelEnum::JUNIOR->value,
            'open_to_relocation' => false,
            'open_to_work' => true,
            'score' => 0,
        ];
    }
}
