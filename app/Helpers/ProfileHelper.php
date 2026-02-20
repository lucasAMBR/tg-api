<?php

namespace App\Helpers;

use App\Exceptions\ApiException;
use App\Models\User;

class ProfileHelper
{
    public static function getUserProfileByRole(User $user): Mixed
    {
        $profile = null;

        $adminProfile = match (true) {
            $user->admin_active_profile === 'dev' => $user->dev_profile,
            $user->admin_active_profile === 'company' => $user->company_profile,
            $user->admin_active_profile === 'client' => $user->client_profile,
            default => null
        };

        $profile = match (true) {
            $user->hasRole('dev') => $user->dev_profile,
            $user->hasRole('company') => $user->company_profile,
            $user->hasRole('client') => $user->client_profile,
            $user->hasRole('admin') => $adminProfile
        };

        if(!$profile){
            throw new ApiException("This action needs a existent profile to proceed!");
        }

        return $profile;
    }

}
