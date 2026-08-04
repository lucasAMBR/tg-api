<?php

namespace App\Services\User;

use App\Exceptions\ApiException;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function update(array $data): UserResource
    {
        $user = User::findOrFail($data['id']);

        $this->ensureAuthorized('update', $user);

        $authUser = Auth::user();

        $data = Arr::except($data, ['id']);

        return DB::transaction(function () use ($authUser, $user, $data) {
            $user->fill($data);

            // 2. Lógica de senha (Melhorada)
            if (isset($data['new_password'])) {
                if (Hash::check($data['old_password'], $authUser->password)) {
                    $user->password = Hash::make($data['new_password']);
                }else{
                    throw new ApiException("Incorrect actual password!");
                }
            }

            // 3. Salva as alterações de fato
            $user->save();

            // 4. Retorna a instância do Model $user para o Resource
            return new UserResource($user);
        });
    }

    public function blockUserAccess(array $data)
    {
        $user = User::findOrFail($data['id']);

        $user->update([
            'is_blocked' => true
        ]);

        return new UserResource($user);
    }

    public function unblockUserAccess(array $data)
    {
        $user = User::findOrFail($data['id']);

        $user->update([
            'is_blocked' => false
        ]);

        return new UserResource($user);
    }

    public function delete(array $data)
    {
        $user = User::findOrFail($data['id']);

        $this->ensureAuthorized('delete', $user);

        $user->delete();
    }

    private function ensureAuthorized(string $ability, User $user): void
    {
        if (Gate::denies($ability, $user)) {
            throw new ApiException('You are not allowed to manage this account!', 403);
        }
    }
}
