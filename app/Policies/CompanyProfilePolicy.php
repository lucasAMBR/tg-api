<?php

namespace App\Policies;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CompanyProfile $companyProfile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CompanyProfile $companyProfile): bool
    {
        if($user->hasRole('admin')) {
            return true;
        }

        if(!$user->can('company_profile.update')) {
            return false;
        }

        // Retorna que o dev só pode se o id for o mesmo do user logado
        return $companyProfile->user_id === $user->$user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CompanyProfile $companyProfile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CompanyProfile $companyProfile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CompanyProfile $companyProfile): bool
    {
        return false;
    }
}
