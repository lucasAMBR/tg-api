<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Http\Resources\User\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(Array $data): Array
    {
        DB::transaction(function () use ($data) {
            $user = User::create($data);

            $user->assignRole($data['role']);
        });

        $loginResponse = $this->login([
            'email'    => $data['email'],
            'password' => $data['password']
        ]);

        return $loginResponse;
    }

    public function login(Array $data): Array
    {
        $user = User::where('email', $data['email'])->first();

        if($user && Auth::attempt(['email' => $data['email'], 'password' => $data['password']])){
            $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
            $token = JWTAuth::fromUser($user);

            if($user->hasRole('dev')){
                $user->load('dev_profile');
            }

            if($user->hasRole('company')){
                $user->load('company_profile');
            }

            if($user->hasRole('client')){
                $user->load('client_profile');
            }

            return [
                'user' => new UserResource($user),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'token' => $token,
                'refresh_expires_in' => $refreshTtlInSeconds
            ];
        }

        throw new ApiException('Invalid email or password!');
    }

    public function profile(): Array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if($user->hasRole('dev')){
            $user->load('dev_profile');
        }

        if($user->hasRole('company')){
            $user->load('company_profile');
        }

        if($user->hasRole('client')){
            $user->load('client_profile');
        }

        return [
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ];
    }

    public function logout(): Void
    {
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        $auth->logout();
    }

    public function refreshToken(): Array
    {
        $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;

        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        $token = $auth->refresh();

        return [
            'token' => $token,
            'refresh_expires_in' => $refreshTtlInSeconds
        ];
    }
}
