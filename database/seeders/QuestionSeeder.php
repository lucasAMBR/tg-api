<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/questions.json');

        if (!file_exists($path)) {
            $this->command->warn("Arquivo não encontrado: {$path}");
            return;
        }

        $questions = json_decode(file_get_contents($path), true);

        if (!is_array($questions)) {
            $this->command->warn('questions.json inválido ou vazio.');
            return;
        }

        $languageIds = Language::pluck('id', 'slug');

        DB::transaction(function () use ($questions, $languageIds) {
            foreach ($questions as $data) {
                $slug = $data['language_slug'] ?? null;

                $question = Question::firstOrCreate(
                    ['question' => $data['question']],
                    [
                        'difficulty_level' => $data['difficulty_level'],
                        'language_id' => $slug ? ($languageIds[$slug] ?? null) : null,
                        'category' => $data['category'],
                        'ideal_time_to_solve' => $data['ideal_time_to_solve'] ?? 0,
                        'code_snippet' => $data['code_snippet'] ?? null,
                        'is_multiple_choice' => $data['is_multiple_choice'],
                        'seniority_level' => $data['seniority_level'],
                    ]
                );

                if (!$question->wasRecentlyCreated) {
                    continue;
                }

                foreach ($data['responses'] ?? [] as $response) {
                    $question->question_responses()->create([
                        'response' => $response['response'],
                        'is_correct' => $response['is_correct'],
                        'code_snippet' => $response['code_snippet'] ?? null,
                    ]);
                }
            }
        });
    }
}
