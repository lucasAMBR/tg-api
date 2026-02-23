<?php

namespace App\Policies;

use App\Models\EmploymentHistory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmploymentHistoryPolicy
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
    public function view(User $user, EmploymentHistory $employmentHistory): bool
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
    public function update(User $user, EmploymentHistory $employmentHistory): bool
    {
        if($user->hasRole('admin')){
            return true;
        };

        return $employmentHistory->dev_profile->user_id == $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmploymentHistory $employmentHistory): bool
    {
        if($user->hasRole('admin')){
            return true;
        };

        return $employmentHistory->dev_profile->user_id == $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmploymentHistory $employmentHistory): bool
    {
        if($user->hasRole('admin')){
            return true;
        };

        return $employmentHistory->dev_profile->user_id == $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmploymentHistory $employmentHistory): bool
    {
        return false;
    }
}
