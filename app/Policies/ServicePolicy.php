<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * All profiles may list services; only Admin/Comercial can manage them.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial() || $user->isTecnico();
    }

    /**
     * A service may only be seen by a user of the owning company.
     */
    public function view(User $user, Service $service): bool
    {
        return $user->belongsToCompany($service->company);
    }

    /**
     * Creating a service always targets the authenticated user's own company.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial();
    }

    /**
     * Only an Admin/Comercial of the owning company may update a service.
     */
    public function update(User $user, Service $service): bool
    {
        return $this->canManageCompany($user, $service);
    }

    /**
     * Only an Admin/Comercial of the owning company may delete a service.
     */
    public function delete(User $user, Service $service): bool
    {
        return $this->canManageCompany($user, $service);
    }

    protected function canManageCompany(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $user->isComercial())
            && $user->belongsToCompany($service->company);
    }
}