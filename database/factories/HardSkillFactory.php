<?php

namespace Database\Factories;

use App\Enums\HardSkillLevelsEnum;
use App\Models\DevProfile;
use App\Models\HardSkill;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardSkill>
 */
class HardSkillFactory extends Factory
{
    protected $model = HardSkill::class;

    public function definition(): array
    {
        return [
            'dev_profile_id' => DevProfile::factory(),
            'language_id' => Language::query()->inRandomOrder()->value('id'),
            'skill_level' => HardSkillLevelsEnum::INTERMEDIATE->value,
        ];
    }
}
