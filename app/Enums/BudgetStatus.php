<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Refused = 'refused';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /**
     * Statuses this status may transition to, mirroring the real flow of the
     * system. A budget is created as Draft, is sent to the client (Sent) and
     * may then be approved. Any other transition currently has no support in
     * the codebase and is intentionally left blocked.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Approved],
            self::Approved, self::Refused, self::Expired, self::Cancelled => [],
        };
    }

    /**
     * Options offered to the user, always including the current status so an
     * edit that keeps the status unchanged remains valid.
     *
     * @return list<self>
     */
    public function transitionOptions(): array
    {
        return [$this, ...$this->allowedTransitions()];
    }

    /**
     * Whether a move to the given status is allowed. A transition is valid
     * when the target equals the current status (a no-op) or is explicitly
     * listed in {@see allowedTransitions()}. Everything else is rejected.
     */
    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Sent => 'Enviado',
            self::Approved => 'Aprovado',
            self::Refused => 'Recusado',
            self::Expired => 'Expirado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Tailwind badge classes used to render the status pill.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Sent => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::Approved => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Refused => 'bg-red-50 text-red-700 ring-red-200',
            self::Expired => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Cancelled => 'bg-slate-100 text-slate-500 ring-slate-200',
        };
    }
}
