<?php

namespace App\Http\Controllers\Auth;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Services\Auth\AuthService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService){}

    #[Endpoint(operationId: 'register', title: 'Cadastrar usuário', description: '**operationId:** `register` — Cria um novo usuário, atribui a `role` informada e, quando enviado, anexa o arquivo `profile_pic` à coleção de mídia `profile_pic`. Ao final, autentica automaticamente o usuário criado. Em **201**, `data.user` segue o schema **User Resource** (`App\\Http\\Resources\\User\\UserResource`) e a resposta também traz `data.permissions`, `data.token` e `data.refresh_expires_in`.')]
    public function register(StoreUserRequest $request): JsonResponse
    {
        try {
            $loggedUser = $this->authService->register($request->validated());

            return ApiResponse::success($loggedUser, 'User successfuly authenticated!', 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'login', title: 'Login', description: '**operationId:** `login` — Autenticação com `email` e `password`. Contas bloqueadas (`is_blocked`) e credenciais inválidas resultam em erro. Em **200**, `data.user` segue o schema **User Resource** (`App\\Http\\Resources\\User\\UserResource`) — já com o perfil correspondente à role carregado — e a resposta também traz `data.permissions`, `data.token` e `data.refresh_expires_in`.')]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $loggedUser = $this->authService->login($request->validated());

            return ApiResponse::success($loggedUser, 'User successfuly authenticated!', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'profile', title: 'Consultar usuário autenticado', description: '**operationId:** `profile` — Retorna os dados do usuário autenticado com o perfil correspondente à sua role carregado. Em **200**, `data.user` segue o schema **User Resource** (`App\\Http\\Resources\\User\\UserResource`) e `data.permissions` traz a lista de permissões do usuário.')]
    public function profile(): JsonResponse
    {
        try {
            $loggedUser = $this->authService->profile();

            return ApiResponse::success($loggedUser, 'User successfuly authenticated!', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'logout', title: 'Logout', description: '**operationId:** `logout` — Invalida o token JWT atual do usuário autenticado. Em **200**, `data` é `null`.')]
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return ApiResponse::success(null, 'User successfuly unauthenticated!', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'refreshToken', title: 'Renovar token', description: '**operationId:** `refreshToken` — Gera um novo token JWT a partir do token atual (enviado no header `Authorization`). Em **200**, `data.token` traz o novo token e `data.refresh_expires_in` o tempo de vida do refresh em segundos.')]
    public function refreshToken(): JsonResponse
    {
        try {
            $tokenData = $this->authService->refreshToken();

            return ApiResponse::success($tokenData, 'Token refresh successful', 200);
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
