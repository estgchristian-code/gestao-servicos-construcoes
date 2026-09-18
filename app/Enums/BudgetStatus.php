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