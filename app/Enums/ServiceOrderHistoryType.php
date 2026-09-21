<?php

namespace App\Enums;

enum ServiceOrderHistoryType: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case TechnicianChanged = 'technician_changed';
    case ScheduledDateChanged = 'scheduled_date_changed';
    case ExecutionStarted = 'execution_started';
    case ExecutionCompleted = 'execution_completed';
    case AttachmentAdded = 'attachment_added';
    case AttachmentRemoved = 'attachment_removed';

    /**
     * Display label for the history event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Criação da OS',
            self::StatusChanged => 'Alteração de status',
            self::TechnicianChanged => 'Alteração de técnico',
            self::ScheduledDateChanged => 'Alteração de agendamento',
            self::ExecutionStarted => 'Início da execução',
            self::ExecutionCompleted => 'Conclusão da execução',
            self::AttachmentAdded => 'Inclusão de anexo',
            self::AttachmentRemoved => 'Exclusão de anexo',
        };
    }

    /**
     * Tailwind badge classes used to render the history pill.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Created => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            self::StatusChanged => 'bg-slate-100 text-slate-700 ring-slate-300',
            self::TechnicianChanged => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::ScheduledDateChanged => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
            self::ExecutionStarted => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::ExecutionCompleted => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::AttachmentAdded => 'bg-violet-50 text-violet-700 ring-violet-200',
            self::AttachmentRemoved => 'bg-red-50 text-red-600 ring-red-200',
        };
    }
}