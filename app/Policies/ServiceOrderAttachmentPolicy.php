<?php

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderAttachment;
use App\Models\User;

class ServiceOrderAttachmentPolicy
{
    /**
     * Admin/Comercial can manage attachments; an assigned Técnico can only
     * manage (view/upload/delete their own) attachments of the assigned OS.
     */
    public function create(User $user, ServiceOrder $order): bool
    {
        if (! $user->belongsToCompany($order->company)) {
            return false;
        }

        return $user->isAdmin() || $user->isComercial() || $this->isAssignedTechnician($user, $order);
    }

    /**
     * An attachment may be seen by an Admin/Comercial of the owning company or
     * by the Técnico assigned to the related service order.
     */
    public function view(User $user, ServiceOrderAttachment $attachment): bool
    {
        if (! $user->belongsToCompany($attachment->company)) {
            return false;
        }

        if ($user->isAdmin() || $user->isComercial()) {
            return true;
        }

        return $this->isAssignedTechnician($user, $attachment->serviceOrder);
    }

    /**
     * Admin/Comercial of the owning company may delete any attachment; a Técnico
     * may only delete attachments they uploaded themselves on the assigned OS.
     */
    public function delete(User $user, ServiceOrderAttachment $attachment): bool
    {
        if (! $user->belongsToCompany($attachment->company)) {
            return false;
        }

        if ($user->isAdmin() || $user->isComercial()) {
            return true;
        }

        return $this->isAssignedTechnician($user, $attachment->serviceOrder)
            && $attachment->user_id !== null
            && (int) $attachment->user_id === $user->id;
    }

    protected function isAssignedTechnician(User $user, ?ServiceOrder $order): bool
    {
        return $user->isTecnico()
            && $order !== null
            && $order->technician_id !== null
            && (int) $order->technician_id === $user->id;
    }
}