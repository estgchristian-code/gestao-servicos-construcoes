<?php

namespace App\Policies;

use App\Models\ServiceOrderItem;
use App\Models\User;

class ServiceOrderItemPolicy
{
    /**
     * All profiles may view a service order's items; only Admin/Comercial manage them.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial() || $user->isTecnico();
    }

    /**
     * An item may only be seen by a user of the owning company.
     */
    public function view(User $user, ServiceOrderItem $item): bool
    {
        return $user->belongsToCompany($item->serviceOrder?->company);
    }

    /**
     * Only an Admin/Comercial of the owning company may update an item.
     */
    public function update(User $user, ServiceOrderItem $item): bool
    {
        return $this->canManage($user, $item);
    }

    /**
     * Only an Admin/Comercial of the owning company may delete an item.
     */
    public function delete(User $user, ServiceOrderItem $item): bool
    {
        return $this->canManage($user, $item);
    }

    protected function canManage(User $user, ServiceOrderItem $item): bool
    {
        return ($user->isAdmin() || $user->isComercial())
            && $user->belongsToCompany($item->serviceOrder?->company);
    }
}