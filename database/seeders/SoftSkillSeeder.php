<?php

namespace Database\Seeders;

use App\Models\SoftSkill;
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
                'name_pt' => 'Comunicação',
                'i18n_name_key' => 'soft_skills.communication',
                'description' => 'Ability to clearly express ideas and understand others in verbal and written communication.',
                'description_pt' => 'Capacidade de expressar ideias com clareza e compreender outras pessoas na comunicação verbal e escrita.',
                'i18n_description_key' => 'soft_skills.communication_description',
            ],
            [
                'name' => 'Teamwork',
                'name_pt' => 'Trabalho em equipe',
                'i18n_name_key' => 'soft_skills.teamwork',
                'description' => 'Ability to collaborate effectively with others to achieve shared goals.',
                'description_pt' => 'Capacidade de colaborar de forma eficaz com outras pessoas para atingir objetivos compartilhados.',
                'i18n_description_key' => 'soft_skills.teamwork_description',
            ],
            [
                'name' => 'Problem Solving',
                'name_pt' => 'Resolução de problemas',
                'i18n_name_key' => 'soft_skills.problem_solving',
                'description' => 'Ability to analyze situations and develop effective solutions.',
                'description_pt' => 'Capacidade de analisar situações e desenvolver soluções eficazes.',
                'i18n_description_key' => 'soft_skills.problem_solving_description',
            ],
            [
                'name' => 'Adaptability',
                'name_pt' => 'Adaptabilidade',
                'i18n_name_key' => 'soft_skills.adaptability',
                'description' => 'Ability to adjust to new situations, challenges, and changing environments.',
                'description_pt' => 'Capacidade de se adaptar a novas situações, desafios e ambientes em constante mudança.',
                'i18n_description_key' => 'soft_skills.adaptability_description',
            ],
            [
                'name' => 'Time Management',
                'name_pt' => 'Gestão de tempo',
                'i18n_name_key' => 'soft_skills.time_management',
                'description' => 'Ability to prioritize tasks and manage time efficiently to meet deadlines.',
                'description_pt' => 'Capacidade de priorizar tarefas e gerenciar o tempo de forma eficiente para cumprir prazos.',
                'i18n_description_key' => 'soft_skills.time_management_description',
            ],
            [
                'name' => 'Critical Thinking',
                'name_pt' => 'Pensamento crítico',
                'i18n_name_key' => 'soft_skills.critical_thinking',
                'description' => 'Ability to evaluate information objectively and make reasoned decisions.',
                'description_pt' => 'Capacidade de avaliar informações de forma objetiva e tomar decisões fundamentadas.',
                'i18n_description_key' => 'soft_skills.critical_thinking_description',
            ],
            [
                'name' => 'Leadership',
                'name_pt' => 'Liderança',
                'i18n_name_key' => 'soft_skills.leadership',
                'description' => 'Ability to guide, motivate, and support a team towards achieving goals.',
                'description_pt' => 'Capacidade de orientar, motivar e apoiar uma equipe na conquista de objetivos.',
                'i18n_description_key' => 'soft_skills.leadership_description',
            ],
            [
                'name' => 'Conflict Resolution',
                'name_pt' => 'Resolução de conflitos',
                'i18n_name_key' => 'soft_skills.conflict_resolution',
                'description' => 'Ability to manage and resolve disagreements in a constructive way.',
                'description_pt' => 'Capacidade de gerenciar e resolver desentendimentos de forma construtiva.',
                'i18n_description_key' => 'soft_skills.conflict_resolution_description',
            ],
            [
                'name' => 'Emotional Intelligence',
                'name_pt' => 'Inteligência emocional',
                'i18n_name_key' => 'soft_skills.emotional_intelligence',
                'description' => 'Ability to recognize, understand, and manage your own emotions and those of others.',
                'description_pt' => 'Capacidade de reconhecer, compreender e gerenciar as próprias emoções e as de outras pessoas.',
                'i18n_description_key' => 'soft_skills.emotional_intelligence_description',
            ],
            [
                'name' => 'Accountability',
                'name_pt' => 'Responsabilidade',
                'i18n_name_key' => 'soft_skills.accountability',
                'description' => 'Ability to take responsibility for actions, decisions, and their outcomes.',
                'description_pt' => 'Capacidade de assumir responsabilidade por ações, decisões e seus resultados.',
                'i18n_description_key' => 'soft_skills.accountability_description',
            ],
        ];

        foreach ($softSkills as $softSkill) {
            SoftSkill::updateOrCreate(
                ['name' => $softSkill['name']],
                [
                    'name_pt' => $softSkill['name_pt'],
                    'i18n_name_key' => $softSkill['i18n_name_key'],
                    'description' => $softSkill['description'],
                    'description_pt' => $softSkill['description_pt'],
                    'i18n_description_key' => $softSkill['i18n_description_key'],
                ]
            );
        }
    }
}
