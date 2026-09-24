<?php

namespace App\Policies;

use App\Models\BudgetItem;
use App\Models\User;

class BudgetItemPolicy
{
    /**
     * All profiles may view a budget's items; only Admin/Comercial manage them.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial() || $user->isTecnico();
    }

    /**
     * An item may only be seen by a user of the owning company.
     */
    public function view(User $user, BudgetItem $item): bool
    {
        return $user->belongsToCompany($item->budget?->company);
    }

    /**
     * Only an Admin/Comercial of the owning company may update an item.
     */
    public function update(User $user, BudgetItem $item): bool
    {
        return $this->canManage($user, $item);
    }

    /**
     * Only an Admin/Comercial of the owning company may delete an item.
     */
    public function delete(User $user, BudgetItem $item): bool
    {
        return $this->canManage($user, $item);
    }

    protected function canManage(User $user, BudgetItem $item): bool
    {
        if ($item->budget?->service_order_id !== null) {
            return false;
        }

        return ($user->isAdmin() || $user->isComercial())
            && $user->belongsToCompany($item->budget?->company);
    }
}