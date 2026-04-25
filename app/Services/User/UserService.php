<?php

namespace App\Services\User;

use App\Exceptions\ApiException;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function update(User $user, array $data): UserResource
    {
        $authUser = Auth::user();

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

    public function delete(User $user)
    {
        $user->delete();
    }
}
