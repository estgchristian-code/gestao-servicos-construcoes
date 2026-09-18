<?php

namespace App\Enums;

enum ServiceOrderExecutionEventType: string
{
    case Started = 'started';
    case Paused = 'paused';
    case Resumed = 'resumed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Display label for the event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Started => 'Início da execução',
            self::Paused => 'Pausa',
            self::Resumed => 'Retomada',
            self::Completed => 'Conclusão',
            self::Cancelled => 'Cancelamento',
        };
    }

    /**
     * Tailwind badge classes used to render the event pill.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Started => 'bg-green-50 text-green-700 ring-green-200',
            self::Paused => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Resumed => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Cancelled => 'bg-red-50 text-red-600 ring-red-200',
        };
    }
}