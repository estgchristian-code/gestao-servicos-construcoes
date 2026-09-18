<?php

namespace App\Policies;

use App\Models\ServiceOrderExecutionEvent;
use App\Models\User;

class ServiceOrderExecutionEventPolicy
{
    /**
     * All profiles may read the execution history of a service order.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial() || $user->isTecnico();
    }

    /**
     * An event may only be seen by a user of the owning company.
     */
    public function view(User $user, ServiceOrderExecutionEvent $event): bool
    {
        return $user->belongsToCompany($event->company);
    }

    /**
     * Admin/Comercial and technicians (the assigned one for the given order is
     * enforced by the execution workflow) may record new events.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isComercial() || $user->isTecnico();
    }

    /**
     * Execution history is strictly append-only.
     */
    public function update(User $user, ServiceOrderExecutionEvent $event): bool
    {
        return false;
    }

    /**
     * Execution history is strictly append-only.
     */
    public function delete(User $user, ServiceOrderExecutionEvent $event): bool
    {
        return false;
    }
}