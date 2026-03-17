<?php

namespace App\Policies;

use App\Models\CompanyProject;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyProjectPolicy
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
    public function view(User $user, CompanyProject $companyProject): bool
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
    public function update(User $user, CompanyProject $companyProject): bool
    {
        if($user->hasRole('admin')) {
            return true;
        }

        return $companyProject->company_profile_id === $user->company_profile->id;

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CompanyProject $companyProject): bool
    {
        if($user->hasRole('admin')) {
            return true;
        }

        return $companyProject->company_profile_id === $user->company_profile->id;

    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CompanyProject $companyProject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CompanyProject $companyProject): bool
    {
        return false;
    }
}
