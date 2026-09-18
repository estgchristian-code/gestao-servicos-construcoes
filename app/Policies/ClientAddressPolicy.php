<?php

namespace App\Policies;

use App\Models\ClientAddress;
use App\Models\User;

class ClientAddressPolicy
{
    /**
     * Addresses are managed by Admin/Comercial of the client's owning company.
     * A Técnico has no address access (future OS-based rule).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial();
    }

    public function view(User $user, ClientAddress $address): bool
    {
        return $this->canManage($user, $address);
    }

    public function update(User $user, ClientAddress $address): bool
    {
        return $this->canManage($user, $address);
    }

    public function delete(User $user, ClientAddress $address): bool
    {
        return $this->canManage($user, $address);
    }

    protected function canManage(User $user, ClientAddress $address): bool
    {
        return ($user->isAdmin() || $user->isComercial())
            && $user->belongsToCompany($address->client?->company);
    }
}