<?php

namespace App\Http\Controllers\Question;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Question\AddResponseToQuestionRequest;
use App\Http\Requests\Question\IndexQuestionRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionResponseRequest;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Services\Question\QuestionService;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function __construct(protected QuestionService $questionService){}

    public function index(IndexQuestionRequest $request)
    {
        $questions = $this->questionService->indexQuestions($request->validated());

        return ApiResponse::success($questions, "Questions fetched successfully");
    }

    public function show(Question $question)
    {
        $question = $this->questionService->showQuestion($question);

        return ApiResponse::success($question, "Question fetched successfully");
    }

    public function store(StoreQuestionRequest $request)
    {
        $question = $this->questionService->storeQuestion($request->validated());

        return ApiResponse::success($question, "Question created successfully");
    }

    public function update(Question $question, UpdateQuestionRequest $request)
    {
        $question = $this->questionService->updateQuestion($question, $request->validated());

        return ApiResponse::success($question, "Question updated successfully");
    }

    public function deleteQuestion(Question $question)
    {
        $this->questionService->deleteQuestion($question);

        return ApiResponse::success(null, "Question deleted successfully");
    }

    public function addResponse(Question $question, AddResponseToQuestionRequest $request)
    {
        $response = $this->questionService->addResponseToQuestion($question, $request->validated());

        return ApiResponse::success($response, "Response added successfully");
    }

    public function deleteResponse(QuestionResponse $response)
    {
        $this->questionService->deleteResponseFromQuestion($response);

        return ApiResponse::success(null, "Response removed successfully");
    }

    public function updateResponse(QuestionResponse $response, UpdateQuestionResponseRequest $request)
    {
        $updatedResponse = $this->questionService->updateResponse($response, $request->validated());

        return ApiResponse::success($updatedResponse, "Response updated successfully");
    }
}
