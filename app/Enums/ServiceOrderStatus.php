<?php

namespace App\Enums;

enum ServiceOrderStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Scheduled => 'Agendada',
            self::InProgress => 'Em execução',
            self::Paused => 'Pausada',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * Tailwind badge classes used to render the status pill.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Scheduled => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::InProgress => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            self::Paused => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Cancelled => 'bg-red-50 text-red-600 ring-red-200',
        };
    }
}