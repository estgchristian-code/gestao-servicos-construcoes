<?php

namespace App\Support;

use App\Enums\ServiceOrderHistoryType;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderHistory;

/**
 * Central mechanism to register Service Order history events. Every action
 * that must be recorded on the OS timeline goes through this class so the
 * history stays consistent and read-only.
 */
class ServiceOrderHistoryRecorder
{
    /**
     * Record a history event for the given service order.
     */
    public static function record(
        ServiceOrder $order,
        ServiceOrderHistoryType $type,
        string $description,
        ?int $userId = null,
    ): ServiceOrderHistory {
        $history = new ServiceOrderHistory([
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'description' => $description,
        ]);
        $history->service_order_id = $order->id;
        $history->company_id = $order->company_id;
        $history->save();

        return $history;
    }
}