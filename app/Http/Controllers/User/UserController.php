<?php

namespace App\Http\Controllers\User;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\BlockUserAccessRequest;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\IndexPopularUsersRequest;
use App\Http\Requests\User\UnblockUserAccessRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\UserService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(protected UserService $user_service){}

    #[Endpoint(operationId: 'indexPopularUsers', title: 'Listar usuários mais populares', description: '**operationId:** `indexPopularUsers` — Ranking paginado dos usuários com perfil de dev, empresa ou cliente, do mais popular para o menos. A pontuação (`popularity_score`) é a soma das curtidas, comentários (com as respostas) e favoritos que os posts do usuário receberam de outras pessoas, mais o número de seguidores; o engajamento do próprio autor, comentários removidos e posts excluídos não contam, e usuários bloqueados ficam de fora. Filtro opcional por `profile_type` (`dev`, `company` ou `client`). Em **200**, `data.data[]` segue o schema **Popular User Resource** (`App\\Http\\Resources\\User\\PopularUserResource`), com a composição da pontuação e `is_followed`/`follows_you` em relação ao usuário autenticado, e `data.pagination` traz os metadados de paginação.')]
    public function indexPopular(IndexPopularUsersRequest $request): JsonResponse
    {
        try {
            $users = $this->user_service->indexPopular($request->validated());

            return ApiResponse::success($users, "Popular users indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateUser', title: 'Atualizar usuário', description: '**operationId:** `updateUser` — Atualiza os dados do usuário. Requer autorização via `UserPolicy::update`. Quando `new_password` é enviado, o `old_password` precisa conferir com a senha atual, caso contrário a operação é recusada. Quando `profile_pic` é enviado, o arquivo substitui a foto atual na coleção de mídia `profile_pic` (a coleção é `singleFile`); como o PHP não faz parse de `multipart/form-data` em `PATCH`, use `POST /user/{id}` nesse caso. É também por aqui que o usuário liga/desliga a confirmação de leitura do chat (`read_receipts_enabled`). Em **200**, `data` segue o schema **User Resource** (`App\\Http\\Resources\\User\\UserResource`).')]
    public function update(UpdateUserRequest $request): JsonResponse
    {
        try {
            $updated = $this->user_service->update($request->validated());

            return ApiResponse::success($updated, "User updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'userBlockAccess', title: 'Bloquear usuário', description: '**operationId:** `userBlockAccess` — Marca o usuário como bloqueado (`is_blocked = true`), impedindo novos logins. Requer a permissão `user.block`. Em **200**, `data` segue o schema **User Resource** (`App\\Http\\Resources\\User\\UserResource`).')]
    public function blockUserAccess(BlockUserAccessRequest $request): JsonResponse
    {
        try {
            $blocked = $this->user_service->blockUserAccess($request->validated());

            return ApiResponse::success($blocked, "User blocked with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'userUnblockAccess', title: 'Desbloquear usuário', description: '**operationId:** `userUnblockAccess` — Remove o bloqueio do usuário (`is_blocked = false`), liberando o login novamente. Requer a permissão `user.block`. Em **200**, `data` segue o schema **User Resource** (`App\\Http\\Resources\\User\\UserResource`).')]
    public function unblockUserAccess(UnblockUserAccessRequest $request): JsonResponse
    {
        try {
            $unblocked = $this->user_service->unblockUserAccess($request->validated());

            return ApiResponse::success($unblocked, "User unblocked with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteUser', title: 'Remover usuário', description: '**operationId:** `deleteUser` — Remove a conta do usuário. Requer autorização via `UserPolicy::delete`. Em **200**, `data` é `null`.')]
    public function delete(DeleteUserRequest $request): JsonResponse {
        try {
            $this->user_service->delete($request->validated());

            return ApiResponse::success(null, "Account deleted with success!");
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
