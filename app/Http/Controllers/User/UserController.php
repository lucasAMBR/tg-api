<?php

namespace App\Http\Controllers\User;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\BlockUserAccessRequest;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\UnblockUserAccessRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\UserService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(protected UserService $user_service){}

    #[Endpoint(operationId: 'updateUser', title: 'Atualizar usuário', description: '**operationId:** `updateUser` — Atualiza os dados do usuário. Requer autorização via `UserPolicy::update`. Quando `new_password` é enviado, o `old_password` precisa conferir com a senha atual, caso contrário a operação é recusada. Quando `profile_pic` é enviado, o arquivo substitui a foto atual na coleção de mídia `profile_pic` (a coleção é `singleFile`); como o PHP não faz parse de `multipart/form-data` em `PATCH`, use `POST /user/{id}` nesse caso. Em **200**, `data` segue o schema **User Resource** (`App\\Http\\Resources\\User\\UserResource`).')]
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
