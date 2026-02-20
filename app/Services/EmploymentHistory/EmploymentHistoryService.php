<?php

namespace App\Services\EmploymentHistory;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmploymentHistoryService
{
    public function store(array $data)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if($authUser->hasRoles('admin') && $authUser->admin_active_profile != "dev"){
            throw new ApiException('Your actual active profile is not a dev profile, please change before to try again!');
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        return DB::transaction(function () use ($data) {

        });
    }
}
