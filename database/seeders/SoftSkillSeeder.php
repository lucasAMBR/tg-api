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
                'description' => 'Ability to clearly express ideas and understand others in verbal and written communication.'
            ],
            [
                'name' => 'Teamwork',
                'description' => 'Ability to collaborate effectively with others to achieve common goals.'
            ],
            [
                'name' => 'Problem Solving',
                'description' => 'Ability to analyze situations and find effective solutions to challenges.'
            ],
            [
                'name' => 'Adaptability',
                'description' => 'Ability to adjust to new conditions, technologies, and environments.'
            ],
            [
                'name' => 'Time Management',
                'description' => 'Ability to prioritize tasks and manage time efficiently to meet deadlines.'
            ],
            [
                'name' => 'Critical Thinking',
                'description' => 'Ability to evaluate information objectively and make reasoned decisions.'
            ],
            [
                'name' => 'Leadership',
                'description' => 'Ability to guide, motivate, and support a team towards achieving goals.'
            ],
            [
                'name' => 'Conflict Resolution',
                'description' => 'Ability to manage and resolve disagreements in a constructive way.'
            ],
            [
                'name' => 'Emotional Intelligence',
                'description' => 'Ability to recognize, understand, and manage your own emotions and those of others.'
            ],
            [
                'name' => 'Accountability',
                'description' => 'Ability to take responsibility for actions, decisions, and their outcomes.'
            ]
        ];

        foreach($softSkills as $softSkill){
            SoftSkill::firstOrCreate([
                'name' => $softSkill['name'],
                'description' => $softSkill['description']
            ]);
        }
    }
}
