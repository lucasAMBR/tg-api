<?php

namespace App\Services;

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

            $user->roles()->attach(Role::whereIn('name', ['user'])->pluck('id'));
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

            return [
                'user' => new UserResource($user),
                'token' => $token,
                'refresh_expires_in' => $refreshTtlInSeconds
            ];
        }

        throw new ApiException('Invalid email or password!');
    }

    public function profile(): UserResource
    {
        $user = Auth::user();

        return new UserResource($user);
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
