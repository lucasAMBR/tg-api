<?php

namespace Database\Seeders;

use App\Models\SoftSkill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SoftSkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $softSkills = [
            [
                'name' => 'Communication',
                'i18n_name_key' => 'soft_skills.communication',
                'description' => 'Ability to clearly express ideas and understand others in verbal and written communication.',
                'i18n_description_key' => 'soft_skills.communication_description',
            ],
            [
                'name' => 'Teamwork',
                'i18n_name_key' => 'soft_skills.teamwork',
                'description' => 'Ability to collaborate effectively with others to achieve shared goals.',
                'i18n_description_key' => 'soft_skills.teamwork_description',
            ],
            [
                'name' => 'Problem Solving',
                'i18n_name_key' => 'soft_skills.problem_solving',
                'description' => 'Ability to analyze situations and develop effective solutions.',
                'i18n_description_key' => 'soft_skills.problem_solving_description',
            ],
            [
                'name' => 'Adaptability',
                'i18n_name_key' => 'soft_skills.adaptability',
                'description' => 'Ability to adjust to new situations, challenges, and changing environments.',
                'i18n_description_key' => 'soft_skills.adaptability_description',
            ],
            [
                'name' => 'Time Management',
                'i18n_name_key' => 'soft_skills.time_management',
                'description' => 'Ability to prioritize tasks and manage time efficiently to meet deadlines.',
                'i18n_description_key' => 'soft_skills.time_management_description',
            ],
            [
                'name' => 'Critical Thinking',
                'i18n_name_key' => 'soft_skills.critical_thinking',
                'description' => 'Ability to evaluate information objectively and make reasoned decisions.',
                'i18n_description_key' => 'soft_skills.critical_thinking_description',
            ],
            [
                'name' => 'Leadership',
                'i18n_name_key' => 'soft_skills.leadership',
                'description' => 'Ability to guide, motivate, and support a team towards achieving goals.',
                'i18n_description_key' => 'soft_skills.leadership_description',
            ],
            [
                'name' => 'Conflict Resolution',
                'i18n_name_key' => 'soft_skills.conflict_resolution',
                'description' => 'Ability to manage and resolve disagreements in a constructive way.',
                'i18n_description_key' => 'soft_skills.conflict_resolution_description',
            ],
            [
                'name' => 'Emotional Intelligence',
                'i18n_name_key' => 'soft_skills.emotional_intelligence',
                'description' => 'Ability to recognize, understand, and manage your own emotions and those of others.',
                'i18n_description_key' => 'soft_skills.emotional_intelligence_description',
            ],
            [
                'name' => 'Accountability',
                'i18n_name_key' => 'soft_skills.accountability',
                'description' => 'Ability to take responsibility for actions, decisions, and their outcomes.',
                'i18n_description_key' => 'soft_skills.accountability_description',
            ]
        ];

        foreach($softSkills as $softSkill){
            SoftSkill::firstOrCreate([
                'name' => $softSkill['name'],
                'i18n_name_key' => $softSkill['i18n_name_key'],
                'description' => $softSkill['description'],
                'i18n_description_key' => $softSkill['i18n_description_key']
            ]);
        }
    }
}
