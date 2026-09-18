<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * A company may only be seen by its own users or a platform superadmin.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() || $user->belongsToCompany($company);
    }

    /**
     * Only the company's admin or a platform superadmin may update it.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() || ($user->belongsToCompany($company) && $user->isAdmin());
    }
}
