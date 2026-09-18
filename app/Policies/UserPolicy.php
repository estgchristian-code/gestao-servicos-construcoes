<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class UserPolicy
{
    /**
     * Listing users is restricted to the company admin or a platform superadmin.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || ($user->isActive() && $user->isAdmin());
    }

    /**
     * A user may be seen by themselves, their company admin, or a platform superadmin.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->is($model)) {
            return true;
        }

        return $user->belongsToCompany($model->company) && $user->isAdmin();
    }

    /**
     * Managing users requires the same visibility rules.
     */
    public function update(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    /**
     * A user can only delete other users of their own company as admin;
     * never themselves, never users of another company.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ! $user->is($model)
            && $user->belongsToCompany($model->company)
            && $user->isAdmin();
    }

    /**
     * Only an admin of the user's company (or a superadmin) may invite new users.
     */
    public function create(User $user, ?Company $company = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isActive()
            && $user->isAdmin()
            && ($company === null || $user->belongsToCompany($company));
    }
}
