<?php

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
{
    /**
     * All profiles may list service orders; only Admin/Comercial can manage them.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial() || $user->isTecnico();
    }

    /**
     * A service order may only be seen by a user of the owning company.
     */
    public function view(User $user, ServiceOrder $order): bool
    {
        return $user->belongsToCompany($order->company);
    }

    /**
     * Creating a service order always targets the authenticated user's own company.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial();
    }

    /**
     * Only an Admin/Comercial of the owning company may update a service order.
     */
    public function update(User $user, ServiceOrder $order): bool
    {
        return $this->canManageCompany($user, $order);
    }

    /**
     * Only an Admin/Comercial of the owning company may delete a service order.
     */
    public function delete(User $user, ServiceOrder $order): bool
    {
        return $this->canManageCompany($user, $order);
    }

    protected function canManageCompany(User $user, ServiceOrder $order): bool
    {
        return ($user->isAdmin() || $user->isComercial())
            && $user->belongsToCompany($order->company);
    }
}