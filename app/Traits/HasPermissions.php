<?php

namespace App\Traits;

trait HasPermissions
{
    public function permissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function hasPermission(string $permission)
    {
        return $this->permissions()
            ->pluck('name')
            ->contains($permission);
    }
}
