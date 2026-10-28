<?php

namespace App\Http\Controllers\Follow;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Follow\IndexFollowsRequest;
use App\Http\Requests\Follow\ToggleFollowRequest;
use App\Services\Follow\FollowService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    public function __construct(protected FollowService $followService){}

    #[Endpoint(operationId: 'toggleFollow', title: 'Seguir/deixar de seguir usuário', description: '**operationId:** `toggleFollow` — Alterna se o usuário autenticado segue o usuário `{id}` (id do usuário, não do perfil). Seguir a si mesmo retorna **422**. Em **200**, `data` traz `following` (estado final) e `followers_count` do usuário seguido.')]
    public function toggle(ToggleFollowRequest $request): JsonResponse
    {
        try {
            $follow = $this->followService->toggle($request->validated());

            return ApiResponse::success($follow, "Follow updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexFollowers', title: 'Listar seguidores', description: '**operationId:** `indexFollowers` — Lista paginada de quem segue o usuário `{id}`, do seguidor mais recente para o mais antigo. Em **200**, `data.data[]` segue o schema **Follow Resource** (`App\\Http\\Resources\\Follow\\FollowResource`), com `is_followed` e `follows_you` em relação ao usuário autenticado, e `data.pagination` traz os metadados de paginação.')]
    public function indexFollowers(IndexFollowsRequest $request): JsonResponse
    {
        try {
            $followers = $this->followService->indexFollowers($request->validated());

            return ApiResponse::success($followers, "Followers indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexFollowing', title: 'Listar seguidos', description: '**operationId:** `indexFollowing` — Lista paginada de quem o usuário `{id}` segue, do follow mais recente para o mais antigo. Em **200**, `data.data[]` segue o schema **Follow Resource** (`App\\Http\\Resources\\Follow\\FollowResource`), com `is_followed` e `follows_you` em relação ao usuário autenticado, e `data.pagination` traz os metadados de paginação.')]
    public function indexFollowing(IndexFollowsRequest $request): JsonResponse
    {
        try {
            $following = $this->followService->indexFollowing($request->validated());

            return ApiResponse::success($following, "Following indexed with success!");
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
