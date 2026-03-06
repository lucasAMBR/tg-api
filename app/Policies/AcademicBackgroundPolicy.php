<?php

namespace App\Policies;

use App\Models\AcademicBackground;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AcademicBackgroundPolicy
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
    public function view(User $user, AcademicBackground $academicBackground): bool
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
    public function update(User $user, AcademicBackground $academicBackground): bool
    {
        if($user->hasRole('admin')){
            return true;
        }
        return $academicBackground->dev_profile_id === $user->dev_profile->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AcademicBackground $academicBackground): bool
    {
        if($user->hasRole('admin')){
            return true;
        }
        return $academicBackground->dev_profile_id === $user->dev_profile->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AcademicBackground $academicBackground): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AcademicBackground $academicBackground): bool
    {
        return false;
    }
}
