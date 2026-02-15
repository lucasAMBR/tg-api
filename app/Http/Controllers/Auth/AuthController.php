<?php

namespace App\Http\Controllers\Auth;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StoreUserRequest;

use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService){}

    public function register(StoreUserRequest $request)
    {
        $loggedUser = $this->authService->register($request->validated());

        return ApiResponse::success($loggedUser, 'User successfuly authenticated!', 201);
    }

    public function login(LoginRequest $request){
        $loggedUser = $this->authService->login($request->validated());

        return ApiResponse::success($loggedUser, 'User successfuly authenticated!', 200);
    }

    public function profile()
    {
        $loggedUser = $this->authService->profile();

        return ApiResponse::success($loggedUser, 'User successfuly authenticated!', 200);
    }

    public function logout()
    {
        $this->authService->logout();

        return ApiResponse::success(null, 'User successfuly unauthenticated!', 200);
    }

    public function refreshToken()
    {
        $tokenData = $this->authService->refreshToken();

        return ApiResponse::success($tokenData, 'Token refresh successful', 200);
    }
}
