<?php

namespace App\Http\Controllers\Comment;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\DeleteCommentRequest;
use App\Http\Requests\Comment\IndexCommentRepliesRequest;
use App\Http\Requests\Comment\IndexCommentRequest;
use App\Http\Requests\Comment\ShowCommentRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Services\Comment\CommentService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(protected CommentService $commentService){}

    #[Endpoint(operationId: 'indexComment', title: 'Listar comentários da postagem', description: '**operationId:** `indexComment` — Lista paginada dos comentários raiz de uma postagem (`post_id`). Cada comentário traz um preview da árvore de respostas (até 3 níveis e 3 respostas por nó); `replies_count` é o total real de respostas diretas e `has_more_replies` indica quando há mais para carregar via `indexCommentReplies`. Em **200**, `data.data[]` segue o schema **Comment Resource** (`App\\Http\\Resources\\Comment\\CommentResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexCommentRequest $request): JsonResponse
    {
        try {
            $comments = $this->commentService->index($request->validated());

            return ApiResponse::success($comments, "Comments indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexCommentReplies', title: 'Listar respostas do comentário', description: '**operationId:** `indexCommentReplies` — Respostas diretas de um comentário, paginadas, cada uma com seu próprio preview de árvore. Usado para "ver mais respostas" e "continuar esta thread". Em **200**, `data.data[]` segue o schema **Comment Resource** (`App\\Http\\Resources\\Comment\\CommentResource`) e `data.pagination` traz os metadados de paginação.')]
    public function replies(IndexCommentRepliesRequest $request): JsonResponse
    {
        try {
            $replies = $this->commentService->replies($request->validated());

            return ApiResponse::success($replies, "Replies indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showComment', title: 'Consultar comentário', description: '**operationId:** `showComment` — Retorna um comentário com o preview da sua árvore de respostas. Em **200**, `data` segue o schema **Comment Resource** (`App\\Http\\Resources\\Comment\\CommentResource`).')]
    public function show(ShowCommentRequest $request): JsonResponse
    {
        try {
            $comment = $this->commentService->show($request->validated());

            return ApiResponse::success($comment, "Comment found!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeComment', title: 'Comentar', description: '**operationId:** `storeComment` — Cria um comentário na postagem (`post_id`) ou uma resposta a outro comentário (`parent_id`, que precisa ser da mesma postagem). Em **201**, `data` segue o schema **Comment Resource** (`App\\Http\\Resources\\Comment\\CommentResource`).')]
    public function store(StoreCommentRequest $request): JsonResponse
    {
        try {
            $comment = $this->commentService->store($request->validated());

            return ApiResponse::success($comment, "Comment created with success!", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateComment', title: 'Atualizar comentário', description: '**operationId:** `updateComment` — Atualiza o texto de um comentário. Apenas o autor (`CommentPolicy::update`) e o comentário não pode estar removido. Em **200**, `data` segue o schema **Comment Resource** (`App\\Http\\Resources\\Comment\\CommentResource`).')]
    public function update(UpdateCommentRequest $request): JsonResponse
    {
        try {
            $comment = $this->commentService->update($request->validated());

            return ApiResponse::success($comment, "Comment updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteComment', title: 'Remover comentário', description: '**operationId:** `deleteComment` — Remoção lógica: o comentário continua na árvore (mantendo as respostas), com `is_removed` verdadeiro e o conteúdo substituído por `[removed]`. Permitido ao autor ou a um admin (`CommentPolicy::delete`). Em **200**, `data` é `null`.')]
    public function delete(DeleteCommentRequest $request): JsonResponse
    {
        try {
            $this->commentService->delete($request->validated());

            return ApiResponse::success(message: "Comment removed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
