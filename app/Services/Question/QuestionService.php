<?php

namespace App\Services\Question;

use App\Exceptions\ApiException;
use App\Http\Resources\Question\QuestionCollection;
use App\Http\Resources\Question\QuestionResource;
use App\Http\Resources\Question\QuestionResponseResource;
use App\Jobs\TranslateContentJob;
use App\Models\Question;
use App\Models\QuestionResponse;
use Illuminate\Support\Facades\DB;

class QuestionService
{

    public function indexQuestions(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $difficultyLevel = $data['difficulty_level'] ?? null;
        $languageId = $data['language_id'] ?? null;
        $category = $data['category'] ?? null;
        $seniorityLevel = $data['seniority_level'] ?? null;
        $isMultipleChoice = $data['is_multiple_choice'] ?? null;
        $translationStatus = $data['translation_status'] ?? null;
        
        $questions = Question::query()->with('question_responses')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereAny(['question', 'question_pt', 'question_en'], 'ilike', "%{$search}%")
                    ->orWhereHas('question_responses', function ($q) use ($search) { 
                        $q->whereAny(['response', 'response_pt', 'response_en'], 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('language', function ($q) use ($search) {
                        $q->where('name', 'ilike', "%{$search}%");
                    });
                });
            })
            ->when($difficultyLevel, function ($query) use ($difficultyLevel) {
                $query->where('difficulty_level', $difficultyLevel);
            })
            ->when($languageId, function ($query) use ($languageId) {
                $query->where('language_id', $languageId);
            })
            ->when($category, function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($seniorityLevel, function ($query) use ($seniorityLevel) {
                $query->where('seniority_level', $seniorityLevel);
            })
            ->when($isMultipleChoice, function ($query) use ($isMultipleChoice) {
                $query->where('is_multiple_choice', $isMultipleChoice);
            })
            ->when($translationStatus, function ($query) use ($translationStatus) {
                $query->where('translation_status', $translationStatus);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return new QuestionCollection($questions);
    }


    public function showQuestion(Question $question): QuestionResource
    {
        $question->load(['question_responses', 'language']);

        return new QuestionResource($question);
    }

    public function storeQuestion(array $data): QuestionResource
    {
        return DB::transaction(function () use ($data):QuestionResource {
            $question = Question::create([
                'question' => $data['question'],
                'difficulty_level' => $data['difficulty_level'],
                'language_id' => $data['language_id'] ?? null,
                'category' => $data['category'],
                'ideal_time_to_solve' => $data['ideal_time_to_solve'],
                'code_snippet' => $data['code_snippet'] ?? null,
                'is_multiple_choice' => $data['is_multiple_choice'],
                'seniority_level' => $data['seniority_level'],
            ]);

            TranslateContentJob::dispatch($question);

            if(isset($data['responses'])) {
                foreach($data['responses'] as $response) {
                    $new_response =QuestionResponse::create([
                        'question_id' => $question->id,
                        'response' => $response['response'],
                        'is_correct' => $response['is_correct'],
                        'code_snippet' => $response['code_snippet'] ?? null,
                    ]);

                    TranslateContentJob::dispatch($new_response);
                }
            }

            $question->load('question_responses');

            return new QuestionResource($question);
        });
    }

    public function updateQuestion(Question $question, array $data): QuestionResource
    {
        return DB::transaction(function () use ($question, $data): QuestionResource {
            $question->update([
                'question' => $data['question'],
                'difficulty_level' => $data['difficulty_level'],
                'language_id' => $data['language_id'] ?? null,
                'category' => $data['category'],
                'ideal_time_to_solve' => $data['ideal_time_to_solve'],
                'code_snippet' => $data['code_snippet'] ?? null,
            ]);

            if($data('manual_update_translation')) {
                $question->question_pt = $data['question_pt'];
                $question->question_en = $data['question_en'];
                $question->save();
            }else{
                TranslateContentJob::dispatch($question);
            }

            return new QuestionResource($question);
        });
    }

    public function deleteQuestion(Question $question): void
    {
        DB::transaction(function () use ($question): void {
            $question->delete();
        });
    }

    public function addResponseToQuestion(Question $question, array $data): QuestionResource
    {
        $responsesCount = $question->question_responses()->count();
        $rightResponsesCount = $question->question_responses()->where('is_correct', true)->count();

        if($responsesCount >= 4) {
            throw new ApiException('Maximum number of responses reached');
        }

        if($responsesCount === 3 && $rightResponsesCount === 0 && $data['is_correct'] === false) {
            throw new ApiException('Question must have at least one right response');
        }

        return DB::transaction(function () use ($question, $data): QuestionResource {
            $response = QuestionResponse::create([
                'question_id' => $question->id,
                'response' => $data['response'],
                'is_correct' => $data['is_correct'],
                'code_snippet' => $data['code_snippet'] ?? null,
            ]);

            TranslateContentJob::dispatch($response);

            $question->load('question_responses');

            return new QuestionResource($question);
        });
    }

    public function deleteResponseFromQuestion(QuestionResponse $response): void
    {
        DB::transaction(function () use ($response): void {
            $response->delete();
        });
    }

    public function updateResponse(QuestionResponse $response, array $data): QuestionResponseResource
    {
        return DB::transaction(function () use ($response, $data) {
            $response->update([
                'response' => $data['response'],
                'is_correct' => $data['is_correct'],
                'code_snippet' => $data['code_snippet'] ?? null,
            ]);

            if($data('manual_update_translation')) {
                $response->response_pt = $data['response_pt'];
                $response->response_en = $data['response_en'];
                $response->save();
            }else{
                TranslateContentJob::dispatch($response);
            }

            return new QuestionResponseResource($response);
        });
    }
}