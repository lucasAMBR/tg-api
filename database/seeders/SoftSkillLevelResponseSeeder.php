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
                    'description' => 'Struggles to express ideas clearly and often causes misunderstandings within the team.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Communicates simple ideas but lacks clarity in more complex discussions.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Communicates clearly in most situations and participates in team discussions.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Expresses ideas clearly and ensures team alignment during discussions.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Communicates complex ideas effectively and facilitates productive discussions.',
                    'evaluation_weight' => 5
                ],
            ],
            'Teamwork' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Rarely collaborates and often works in isolation from the team.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Occasionally collaborates but has difficulty aligning with the team.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Works well with teammates and contributes to shared goals.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Actively collaborates and helps improve team productivity.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Promotes strong collaboration and strengthens team dynamics.',
                    'evaluation_weight' => 5
                ],
            ],
            'Problem Solving' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Has difficulty identifying problems or proposing solutions.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Can solve simple problems but struggles with complex situations.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Analyzes problems and proposes reasonable solutions.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Breaks down complex problems and consistently finds effective solutions.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Anticipates problems and designs strategic solutions for complex challenges.',
                    'evaluation_weight' => 5
                ],
            ],
            'Adaptability' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Has significant difficulty adapting to changes in processes, technologies, or team dynamics.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Shows some resistance to change but can adapt with guidance and additional time.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Adapts to new tools, environments, and processes with moderate support.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Quickly adapts to new situations, technologies, and project requirements.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Proactively embraces change and helps the team adapt effectively to new challenges.',
                    'evaluation_weight' => 5
                ],
            ],

            'Time Management' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Struggles to organize tasks and frequently misses deadlines.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Completes tasks but has difficulty prioritizing work and managing deadlines.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Manages time reasonably well and usually delivers tasks within expected deadlines.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Effectively prioritizes work and consistently meets deadlines even under pressure.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Demonstrates exceptional organization and helps the team optimize time and productivity.',
                    'evaluation_weight' => 5
                ],
            ],

            'Critical Thinking' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Has difficulty analyzing information and often relies on assumptions.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Can analyze simple situations but struggles with complex reasoning.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Evaluates information logically and contributes to problem discussions.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Consistently analyzes situations critically and proposes well-reasoned solutions.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Demonstrates exceptional analytical thinking and guides others in making sound decisions.',
                    'evaluation_weight' => 5
                ],
            ],
            'Leadership' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Avoids responsibility for guiding others and struggles to provide direction or support to the team.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Occasionally provides guidance but lacks consistency in motivating or supporting the team.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Supports teammates and helps coordinate efforts to achieve team objectives.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Actively motivates and guides the team while helping resolve challenges and maintain alignment.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Demonstrates strong leadership by inspiring others, guiding decisions, and fostering team growth.',
                    'evaluation_weight' => 5
                ],
            ],

            'Conflict Resolution' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Avoids or escalates conflicts and struggles to contribute to constructive resolutions.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Recognizes conflicts but has difficulty facilitating productive discussions to resolve them.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Participates in resolving disagreements and helps maintain respectful communication.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Effectively mediates conflicts and helps guide discussions toward constructive outcomes.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Proactively identifies potential conflicts and facilitates collaborative and sustainable solutions.',
                    'evaluation_weight' => 5
                ],
            ],

            'Emotional Intelligence' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Struggles to recognize emotions in self or others and may react impulsively in challenging situations.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Shows some awareness of emotions but has difficulty managing reactions consistently.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Recognizes emotional dynamics and generally responds in a respectful and balanced manner.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Demonstrates strong emotional awareness and maintains constructive interactions even in stressful situations.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
                    'description' => 'Exhibits exceptional emotional intelligence and positively influences the emotional dynamics of the team.',
                    'evaluation_weight' => 5
                ],
            ],

            'Accountability' => [
                [
                    'title' => 'Very Poor',
                    'description' => 'Avoids responsibility for outcomes and frequently shifts blame to others.',
                    'evaluation_weight' => 1
                ],
                [
                    'title' => 'Basic',
                    'description' => 'Accepts responsibility when prompted but may struggle to consistently follow through on commitments.',
                    'evaluation_weight' => 2
                ],
                [
                    'title' => 'Intermediate',
                    'description' => 'Takes ownership of tasks and acknowledges responsibility for results.',
                    'evaluation_weight' => 3
                ],
                [
                    'title' => 'Advanced',
                    'description' => 'Consistently demonstrates ownership, reliability, and responsibility for outcomes.',
                    'evaluation_weight' => 4
                ],
                [
                    'title' => 'Expert',
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
                    'title' => $response['title'],
                    'description' => $response['description'],
                    'evaluation_weight' => $response['evaluation_weight']
                ]);
            }
        }
    }
}
