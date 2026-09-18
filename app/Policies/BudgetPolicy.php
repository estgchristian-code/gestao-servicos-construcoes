<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    /**
     * All profiles may list budgets; only Admin/Comercial can manage them.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial() || $user->isTecnico();
    }

    /**
     * A budget may only be seen by a user of the owning company.
     */
    public function view(User $user, Budget $budget): bool
    {
        return $user->belongsToCompany($budget->company);
    }

    /**
     * Creating a budget always targets the authenticated user's own company.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial();
    }

    /**
     * Only an Admin/Comercial of the owning company may update a budget.
     */
    public function update(User $user, Budget $budget): bool
    {
        return $this->canManageCompany($user, $budget);
    }

    /**
     * Only an Admin/Comercial of the owning company may delete a budget.
     */
    public function delete(User $user, Budget $budget): bool
    {
        return $this->canManageCompany($user, $budget);
    }

    /**
     * Only an Admin/Comercial of the owning company may convert an approved
     * budget into a service order.
     */
    public function convert(User $user, Budget $budget): bool
    {
        return $this->canManageCompany($user, $budget);
    }

    protected function canManageCompany(User $user, Budget $budget): bool
    {
        return ($user->isAdmin() || $user->isComercial())
            && $user->belongsToCompany($budget->company);
    }
}