<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Admin and Comercial can list their own company's clients.
     *
     * Técnico currently has no list access. When Ordens de Serviço exist, a
     * scoped rule (clients related to the technician's assigned OS) should be
     * added here instead of granting broad access.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial();
    }

    /**
     * A client may only be seen by an Admin/Comercial of the owning company.
     */
    public function view(User $user, Client $client): bool
    {
        return $this->canManageCompany($user, $client);
    }

    /**
     * Creating a client always targets the authenticated user's own company.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial();
    }

    /**
     * Only an Admin/Comercial of the owning company may update a client.
     */
    public function update(User $user, Client $client): bool
    {
        return $this->canManageCompany($user, $client);
    }

    /**
     * Only an Admin/Comercial of the owning company may delete a client.
     */
    public function delete(User $user, Client $client): bool
    {
        return $this->canManageCompany($user, $client);
    }

    /**
     * Restoring a soft-deleted client follows the same rules as updating.
     */
    public function restore(User $user, Client $client): bool
    {
        return $this->canManageCompany($user, $client);
    }

    protected function canManageCompany(User $user, Client $client): bool
    {
        return ($user->isAdmin() || $user->isComercial())
            && $user->belongsToCompany($client->company);
    }
}