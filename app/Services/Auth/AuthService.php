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
    /**
     * @return array{user: UserResource, permissions: list<string>, token: string, refresh_expires_in: int}
     */
    public function register(Array $data): Array
    {
        DB::transaction(function () use ($data) {
            $user = User::create($data);

            $user->assignRole($data['role']);

            if(isset($data['profile_pic'])){
                if ($data['profile_pic'] instanceof \Illuminate\Http\UploadedFile) {
                    $user->addMedia($data['profile_pic'])
                        ->toMediaCollection('profile_pic');
                } else {
                    throw new \Exception('profile_pic precisa ser um UploadedFile.');
                }
            }
        });

        $loginResponse = $this->login([
            'email'    => $data['email'],
            'password' => $data['password']
        ]);

        return $loginResponse;
    }

    /**
     * @return array{user: UserResource, permissions: list<string>, token: string, refresh_expires_in: int}
     */
    public function login(Array $data): Array
    {
        $user = User::where('email', $data['email'])->first();

        if($user && Auth::attempt(['email' => $data['email'], 'password' => $data['password']])){
            if($user->is_blocked){
                throw new ApiException('Your account has been blocked by the adminstrations team. Please contact support.');
            }
            
            $refreshTtlInSeconds = (int) Config::get('jwt.refresh_ttl') * 60;
            $token = JWTAuth::fromUser($user);

            $user->load('media');

            if($user->hasRole('dev')){
                $user->load('dev_profile');
            }

            if($user->hasRole('company')){
                $user->load('company_profile');
            }

            if($user->hasRole('client')){
                $user->load('client_profile');
            }

            if($user->hasRole('admin')){
                $user->load('admin_profile');
            }

            return [
                'user' => new UserResource($user),
                'permissions' => $this->permissionNames($user),
                'token' => $token,
                'refresh_expires_in' => (int) $refreshTtlInSeconds
            ];
        }

        throw new ApiException('Invalid email or password!');
    }

    /**
     * @return array{user: UserResource, permissions: list<string>}
     */
    public function profile(): Array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->load('media');

        if($user->hasRole('dev')){
            $user->load('dev_profile');
        }

        if($user->hasRole('company')){
            $user->load('company_profile');
        }

        if($user->hasRole('client')){
            $user->load('client_profile');
        }

        if($user->hasRole('admin')){
            $user->load('admin_profile');
        }

        return [
            'user' => new UserResource($user),
            'permissions' => $this->permissionNames($user)
        ];
    }

    /**
     * Nomes das permissões do usuário.
     *
     * O retorno de `getAllPermissions()` é uma Collection que o Scramble não
     * consegue tipar (a annotation da relation acaba vazando para a spec),
     * então normalizamos para uma lista de strings.
     *
     * @return list<string>
     */
    private function permissionNames(User $user): array
    {
        $names = [];

        foreach ($user->getAllPermissions() as $permission) {
            $names[] = (string) $permission->name;
        }

        return $names;
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
            'refresh_expires_in' => (int) $refreshTtlInSeconds
        ];
    }
}
