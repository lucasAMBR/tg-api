<?php

namespace App\Http\Controllers\User;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected UserService $user_service){}

    public function update(User $user, UpdateUserRequest $request)
    {
        $this->authorize('update', $user);

        $updated = $this->user_service->update($user, $request->validated());

        return ApiResponse::success($updated, "User updated with success!");
    }

    public function delete(User $user){
        $this->authorize('delete', $user);

        $this->user_service->delete($user);

        return ApiResponse::success(null, "Account deleted with success!");
    }

}
