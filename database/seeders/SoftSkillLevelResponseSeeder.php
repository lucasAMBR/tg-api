<?php

namespace Database\Seeders;

use App\Models\SoftSkill;
use App\Models\SoftSkillLevelResponse;
use Illuminate\Database\Seeder;

class SoftSkillLevelResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $softSkillResponses = [
            'Communication' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.communication_very_poor',
                    'description' => 'Struggles to express ideas clearly and often causes misunderstandings within the team.',
                    'i18n_description_key' => 'soft_skill_level_responses.communication_very_poor_description',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.communication_basic',
                    'description' => 'Communicates simple ideas but lacks clarity in more complex discussions.',
                    'i18n_description_key' => 'soft_skill_level_responses.communication_basic_description',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.communication_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.communication_intermediate_description',
                    'description' => 'Communicates clearly in most situations and participates in team discussions.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.communication_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.communication_advanced_description',
                    'description' => 'Expresses ideas clearly and ensures team alignment during discussions.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.communication_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.communication_expert_description',
                    'description' => 'Communicates complex ideas effectively and facilitates productive discussions.',
                    'evaluation_weight' => 5
                ],
            ],
            'Teamwork' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.teamwork_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.teamwork_very_poor_description',
                    'description' => 'Rarely collaborates and often works in isolation from the team.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.teamwork_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.teamwork_basic_description',
                    'description' => 'Occasionally collaborates but has difficulty aligning with the team.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.teamwork_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.teamwork_intermediate_description',
                    'description' => 'Works well with teammates and contributes to shared goals.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.teamwork_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.teamwork_advanced_description',
                    'description' => 'Actively collaborates and helps improve team productivity.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.teamwork_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.teamwork_expert_description',
                    'description' => 'Promotes strong collaboration and strengthens team dynamics.',
                    'evaluation_weight' => 5
                ],
            ],
            'Problem Solving' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.problem_solving_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.problem_solving_very_poor_description',
                    'description' => 'Has difficulty identifying problems or proposing solutions.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.problem_solving_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.problem_solving_basic_description',
                    'description' => 'Can solve simple problems but struggles with complex situations.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.problem_solving_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.problem_solving_intermediate_description',
                    'description' => 'Analyzes problems and proposes reasonable solutions.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.problem_solving_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.problem_solving_advanced_description',
                    'description' => 'Breaks down complex problems and consistently finds effective solutions.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.problem_solving_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.problem_solving_expert_description',
                    'description' => 'Anticipates problems and designs strategic solutions for complex challenges.',
                    'evaluation_weight' => 5
                ],
            ],
            'Adaptability' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.adaptability_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.adaptability_very_poor_description',
                    'description' => 'Has significant difficulty adapting to changes in processes, technologies, or team dynamics.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.adaptability_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.adaptability_basic_description',
                    'description' => 'Shows some resistance to change but can adapt with guidance and additional time.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.adaptability_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.adaptability_intermediate_description',
                    'description' => 'Adapts to new tools, environments, and processes with moderate support.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.adaptability_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.adaptability_advanced_description',
                    'description' => 'Quickly adapts to new situations, technologies, and project requirements.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.adaptability_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.adaptability_expert_description',
                    'description' => 'Proactively embraces change and helps the team adapt effectively to new challenges.',
                    'evaluation_weight' => 5
                ],
            ],

            'Time Management' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.time_management_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.time_management_very_poor_description',
                    'description' => 'Struggles to organize tasks and frequently misses deadlines.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.time_management_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.time_management_basic_description',
                    'description' => 'Completes tasks but has difficulty prioritizing work and managing deadlines.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.time_management_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.time_management_intermediate_description',
                    'description' => 'Manages time reasonably well and usually delivers tasks within expected deadlines.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.time_management_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.time_management_advanced_description',
                    'description' => 'Effectively prioritizes work and consistently meets deadlines even under pressure.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.time_management_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.time_management_expert_description',
                    'description' => 'Demonstrates exceptional organization and helps the team optimize time and productivity.',
                    'evaluation_weight' => 5
                ],
            ],

            'Critical Thinking' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.critical_thinking_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.critical_thinking_very_poor_description',
                    'description' => 'Has difficulty analyzing information and often relies on assumptions.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.critical_thinking_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.critical_thinking_basic_description',
                    'description' => 'Can analyze simple situations but struggles with complex reasoning.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.critical_thinking_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.critical_thinking_intermediate_description',
                    'description' => 'Evaluates information logically and contributes to problem discussions.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.critical_thinking_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.critical_thinking_advanced_description',
                    'description' => 'Consistently analyzes situations critically and proposes well-reasoned solutions.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.critical_thinking_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.critical_thinking_expert_description',
                    'description' => 'Demonstrates exceptional analytical thinking and guides others in making sound decisions.',
                    'evaluation_weight' => 5
                ],
            ],
            'Leadership' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.leadership_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.leadership_very_poor_description',
                    'description' => 'Avoids responsibility for guiding others and struggles to provide direction or support to the team.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.leadership_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.leadership_basic_description',
                    'description' => 'Occasionally provides guidance but lacks consistency in motivating or supporting the team.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.leadership_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.leadership_intermediate_description',
                    'description' => 'Supports teammates and helps coordinate efforts to achieve team objectives.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.leadership_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.leadership_advanced_description',
                    'description' => 'Actively motivates and guides the team while helping resolve challenges and maintain alignment.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.leadership_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.leadership_expert_description',
                    'description' => 'Demonstrates strong leadership by inspiring others, guiding decisions, and fostering team growth.',
                    'evaluation_weight' => 5
                ],
            ],

            'Conflict Resolution' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.conflict_resolution_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.conflict_resolution_very_poor_description',
                    'description' => 'Avoids or escalates conflicts and struggles to contribute to constructive resolutions.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.conflict_resolution_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.conflict_resolution_basic_description',
                    'description' => 'Recognizes conflicts but has difficulty facilitating productive discussions to resolve them.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.conflict_resolution_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.conflict_resolution_intermediate_description',
                    'description' => 'Participates in resolving disagreements and helps maintain respectful communication.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.conflict_resolution_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.conflict_resolution_advanced_description',
                    'description' => 'Effectively mediates conflicts and helps guide discussions toward constructive outcomes.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.conflict_resolution_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.conflict_resolution_expert_description',
                    'description' => 'Proactively identifies potential conflicts and facilitates collaborative and sustainable solutions.',
                    'evaluation_weight' => 5
                ],
            ],

            'Emotional Intelligence' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.emotional_intelligence_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.emotional_intelligence_very_poor_description',
                    'description' => 'Struggles to recognize emotions in self or others and may react impulsively in challenging situations.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.emotional_intelligence_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.emotional_intelligence_basic_description',
                    'description' => 'Shows some awareness of emotions but has difficulty managing reactions consistently.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.emotional_intelligence_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.emotional_intelligence_intermediate_description',
                    'description' => 'Recognizes emotional dynamics and generally responds in a respectful and balanced manner.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.emotional_intelligence_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.emotional_intelligence_advanced_description',
                    'description' => 'Demonstrates strong emotional awareness and maintains constructive interactions even in stressful situations.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.emotional_intelligence_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.emotional_intelligence_expert_description',
                    'description' => 'Exhibits exceptional emotional intelligence and positively influences the emotional dynamics of the team.',
                    'evaluation_weight' => 5
                ],
            ],

            'Accountability' => [
                [
                    'title' => 'Very Poor',
                    'i18n_title_key' => 'soft_skill_level_responses.accountability_very_poor',
                    'i18n_description_key' => 'soft_skill_level_responses.accountability_very_poor_description',
                    'description' => 'Avoids responsibility for outcomes and frequently shifts blame to others.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'i18n_title_key' => 'soft_skill_level_responses.accountability_basic',
                    'i18n_description_key' => 'soft_skill_level_responses.accountability_basic_description',
                    'description' => 'Accepts responsibility when prompted but may struggle to consistently follow through on commitments.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'i18n_title_key' => 'soft_skill_level_responses.accountability_intermediate',
                    'i18n_description_key' => 'soft_skill_level_responses.accountability_intermediate_description',
                    'description' => 'Takes ownership of tasks and acknowledges responsibility for results.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'i18n_title_key' => 'soft_skill_level_responses.accountability_advanced',
                    'i18n_description_key' => 'soft_skill_level_responses.accountability_advanced_description',
                    'description' => 'Consistently demonstrates ownership, reliability, and responsibility for outcomes.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'i18n_title_key' => 'soft_skill_level_responses.accountability_expert',
                    'i18n_description_key' => 'soft_skill_level_responses.accountability_expert_description',
                    'description' => 'Leads by example in accountability and encourages a culture of ownership within the team.',
                    'evaluation_weight' => 5
                ],
            ]
        ];

        foreach($softSkillResponses as $skill => $responses){
            $softSkill = SoftSkill::where('name', $skill)->first();

            if(!$softSkill){
                continue;
            }

            foreach($responses as $response){
                SoftSkillLevelResponse::firstOrCreate([
                    'soft_skill_id' => $softSkill->id,
                    'evaluation_weight' => $response['evaluation_weight'],
                ], [
                    'title' => $response['title'],
                    'i18n_title_key' => $response['i18n_title_key'],
                    'description' => $response['description'],
                    'i18n_description_key' => $response['i18n_description_key'],
                ]);
            }
        }
    }
}
