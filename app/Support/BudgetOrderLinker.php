<?php

namespace App\Support;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\ServiceOrder;
use Illuminate\Validation\ValidationException;

/**
 * Central mechanism to keep the budget ⇄ service order link consistent on
 * both sides (service_orders.budget_id and budgets.service_order_id). Every
 * manual create/edit/delete path must go through these helpers so the link
 * never ends up pointing to the wrong record.
 */
class BudgetOrderLinker
{
    /**
     * Load a budget with a pessimistic lock so two concurrent requests cannot
     * link the same budget to different service orders.
     */
    public static function lockBudget(int $budgetId): ?Budget
    {
        return Budget::query()->whereKey($budgetId)->lockForUpdate()->first();
    }

    /**
     * Lock a budget or fail the request with a validation error when it no
     * longer exists.
     */
    public static function lockBudgetOrFail(int $budgetId): Budget
    {
        $budget = static::lockBudget($budgetId);

        if ($budget === null) {
            throw ValidationException::withMessages([
                'budget_id' => 'O orçamento selecionado não está mais disponível.',
            ]);
        }

        return $budget;
    }

    /**
     * Ensure a budget is linkable to the given service order. A budget is only
     * linkable when it belongs to the same company and client, is approved and
     * is not already linked to another order. The currently linked budget of an
     * order may always be preserved on edit.
     */
    public static function assertLinkable(
        ?Budget $budget,
        int $companyId,
        int $clientId,
        ?int $orderId = null,
        ?int $currentBudgetId = null,
    ): void {
        if ($budget === null) {
            return;
        }

        $isCurrentBudget = $currentBudgetId !== null && $budget->getKey() === $currentBudgetId;

        $linkable = $budget->company_id === $companyId
            && $budget->client_id === $clientId
            && (
                $isCurrentBudget
                || ($budget->status === BudgetStatus::Approved
                    && ($budget->service_order_id === null || $budget->service_order_id === $orderId))
            );

        if (! $linkable) {
            throw ValidationException::withMessages([
                'budget_id' => 'O orçamento selecionado não pode ser vinculado a esta ordem de serviço.',
            ]);
        }
    }

    /**
     * Point the budget back-reference to the given service order.
     */
    public static function attach(ServiceOrder $order, Budget $budget): void
    {
        Budget::query()->whereKey($budget->getKey())->update(['service_order_id' => $order->id]);
    }

    /**
     * Release a budget back-reference only when it still points to the given
     * service order. If the budget was already re-linked to another order, it
     * is left untouched.
     */
    public static function releaseFromOrder(int $budgetId, int $orderId): void
    {
        Budget::query()
            ->whereKey($budgetId)
            ->where('service_order_id', $orderId)
            ->update(['service_order_id' => null]);
    }
}
