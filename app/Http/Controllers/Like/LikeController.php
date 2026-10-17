<?php

namespace App\Http\Controllers\Like;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Like\IndexCommentLikesRequest;
use App\Http\Requests\Like\IndexPostLikesRequest;
use App\Http\Requests\Like\ToggleCommentLikeRequest;
use App\Http\Requests\Like\TogglePostLikeRequest;
use App\Services\Like\LikeService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function __construct(protected LikeService $likeService){}

    #[Endpoint(operationId: 'togglePostLike', title: 'Curtir/descurtir postagem', description: '**operationId:** `togglePostLike` — Alterna a curtida do usuário autenticado na postagem. Em **200**, `data` traz `liked` (estado final) e `likes_count`.')]
    public function togglePost(TogglePostLikeRequest $request): JsonResponse
    {
        try {
            $like = $this->likeService->togglePostLike($request->validated());

            return ApiResponse::success($like, "Post like updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'toggleCommentLike', title: 'Curtir/descurtir comentário', description: '**operationId:** `toggleCommentLike` — Alterna a curtida do usuário autenticado no comentário. Em **200**, `data` traz `liked` (estado final) e `likes_count`.')]
    public function toggleComment(ToggleCommentLikeRequest $request): JsonResponse
    {
        try {
            $like = $this->likeService->toggleCommentLike($request->validated());

            return ApiResponse::success($like, "Comment like updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexPostLikes', title: 'Listar curtidas da postagem', description: '**operationId:** `indexPostLikes` — Lista paginada de quem curtiu a postagem, das curtidas mais recentes para as mais antigas. Em **200**, `data.data[]` segue o schema **Like Resource** (`App\\Http\\Resources\\Like\\LikeResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexPost(IndexPostLikesRequest $request): JsonResponse
    {
        try {
            $likes = $this->likeService->indexPostLikes($request->validated());

            return ApiResponse::success($likes, "Post likes indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexCommentLikes', title: 'Listar curtidas do comentário', description: '**operationId:** `indexCommentLikes` — Lista paginada de quem curtiu o comentário, das curtidas mais recentes para as mais antigas. Em **200**, `data.data[]` segue o schema **Like Resource** (`App\\Http\\Resources\\Like\\LikeResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexComment(IndexCommentLikesRequest $request): JsonResponse
    {
        try {
            $likes = $this->likeService->indexCommentLikes($request->validated());

            return ApiResponse::success($likes, "Comment likes indexed with success!");
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
