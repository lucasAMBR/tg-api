<?php

namespace App\Policies;

use App\Models\HardSkill;
use App\Models\User;

class HardSkillPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, HardSkill $hardSkill): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, HardSkill $hardSkill): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $hardSkill->dev_profile_id === $user->dev_profile?->id;
    }

    public function delete(User $user, HardSkill $hardSkill): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $hardSkill->dev_profile_id === $user->dev_profile?->id;
    }

    public function restore(User $user, HardSkill $hardSkill): bool
    {
        return false;
    }

    public function forceDelete(User $user, HardSkill $hardSkill): bool
    {
        return false;
    }
}
