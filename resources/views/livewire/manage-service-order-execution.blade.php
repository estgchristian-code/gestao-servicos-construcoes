<?php

use App\Enums\ServiceOrderExecutionEventType;
use App\Enums\ServiceOrderHistoryType;
use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderExecutionEvent;
use App\Support\ServiceOrderHistoryRecorder;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ServiceOrder $order;

    public string $event_notes = '';

    public function mount(ServiceOrder $order): void
    {
        $this->order = $order;

        $this->authorize('view', $this->order);
    }

    #[Computed]
    public function events()
    {
        return $this->order->executionEvents()->with('user')->get();
    }

    #[Computed]
    public function canExecute(): bool
    {
        $user = auth()->user();

        if (! $user->belongsToCompany($this->order->company)) {
            return false;
        }

        if ($user->isAdmin() || $user->isComercial()) {
            return true;
        }

        return $user->isTecnico()
            && $this->order->technician_id !== null
            && (int) $this->order->technician_id === $user->id;
    }

    public function start(): void
    {
        $this->register(
            ServiceOrderExecutionEventType::Started,
            ServiceOrderStatus::InProgress,
            [ServiceOrderStatus::Pending, ServiceOrderStatus::Scheduled]
        );
    }

    public function pause(): void
    {
        $this->register(
            ServiceOrderExecutionEventType::Paused,
            ServiceOrderStatus::Paused,
            [ServiceOrderStatus::InProgress]
        );
    }

    public function resume(): void
    {
        $this->register(
            ServiceOrderExecutionEventType::Resumed,
            ServiceOrderStatus::InProgress,
            [ServiceOrderStatus::Paused]
        );
    }

    public function complete(): void
    {
        $this->register(
            ServiceOrderExecutionEventType::Completed,
            ServiceOrderStatus::Completed,
            [ServiceOrderStatus::InProgress, ServiceOrderStatus::Paused]
        );
    }

    public function cancel(): void
    {
        $this->register(
            ServiceOrderExecutionEventType::Cancelled,
            ServiceOrderStatus::Cancelled,
            [
                ServiceOrderStatus::Pending,
                ServiceOrderStatus::Scheduled,
                ServiceOrderStatus::InProgress,
                ServiceOrderStatus::Paused,
            ]
        );
    }

    protected function register(
        ServiceOrderExecutionEventType $type,
        ServiceOrderStatus $target,
        array $allowedFrom,
    ): void {
        if (! $this->canExecute) {
            abort(403, 'Você não tem permissão para registrar eventos de execução desta OS.');
        }

        if (! in_array($this->order->status, $allowedFrom, true)) {
            abort(403, 'Transição de status não permitida a partir do status atual.');
        }

        $notes = trim($this->event_notes);
        $notes = $notes !== '' ? $notes : null;

        $event = new ServiceOrderExecutionEvent([
            'type' => $type,
            'notes' => $notes,
        ]);
        $event->service_order_id = $this->order->id;
        $event->company_id = $this->order->company_id;
        $event->user_id = auth()->id();
        $event->save();

        $this->order->update(['status' => $target]);

        ServiceOrderHistoryRecorder::record(
            $this->order,
            $this->historyTypeFor($type),
            $this->historyDescriptionFor($type, $target)
        );

        $this->dispatch('order-status-updated');

        $this->event_notes = '';
        $this->resetValidation();
        unset($this->events);
        session()->flash('status', $type->label() . ' registrado com sucesso.');
    }

    protected function historyTypeFor(ServiceOrderExecutionEventType $type): ServiceOrderHistoryType
    {
        return match ($type) {
            ServiceOrderExecutionEventType::Started => ServiceOrderHistoryType::ExecutionStarted,
            ServiceOrderExecutionEventType::Completed => ServiceOrderHistoryType::ExecutionCompleted,
            ServiceOrderExecutionEventType::Paused,
            ServiceOrderExecutionEventType::Resumed,
            ServiceOrderExecutionEventType::Cancelled => ServiceOrderHistoryType::StatusChanged,
        };
    }

    protected function historyDescriptionFor(ServiceOrderExecutionEventType $type, ServiceOrderStatus $target): string
    {
        return match ($type) {
            ServiceOrderExecutionEventType::Started => 'Execução iniciada.',
            ServiceOrderExecutionEventType::Completed => 'Execução concluída.',
            ServiceOrderExecutionEventType::Paused,
            ServiceOrderExecutionEventType::Resumed,
            ServiceOrderExecutionEventType::Cancelled => 'Status alterado para ' . $target->label() . '.',
        };
    }
};
?>
<div class="space-y-4">
    @if ($this->canExecute && ! in_array($this->order->status, [ServiceOrderStatus::Completed, ServiceOrderStatus::Cancelled], true))
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Execução da ordem de serviço</h2>
                <p class="mt-0.5 text-xs text-slate-500">Registre o andamento e o resultado da execução.</p>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="execution-notes" class="mb-1 block text-sm font-medium text-slate-700">Observação / descrição da execução</label>
                    <textarea id="execution-notes" wire:model="event_notes" rows="3" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="Descreva o que foi feito, condições encontradas, resultado... (opcional)"></textarea>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if (in_array($this->order->status, [ServiceOrderStatus::Pending, ServiceOrderStatus::Scheduled], true))
                        <button type="button" wire:click="start"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                            Iniciar execução
                        </button>
                    @endif

                    @if ($this->order->status === ServiceOrderStatus::InProgress)
                        <button type="button" wire:click="pause"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Pausar
                        </button>
                        <button type="button" wire:click="complete"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700">
                            Concluir
                        </button>
                        <button type="button" wire:click="cancel" wire:confirm="Cancelar esta ordem de serviço?"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                            Cancelar
                        </button>
                    @endif

                    @if ($this->order->status === ServiceOrderStatus::Paused)
                        <button type="button" wire:click="resume"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                            Retomar
                        </button>
                        <button type="button" wire:click="complete"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700">
                            Concluir
                        </button>
                        <button type="button" wire:click="cancel" wire:confirm="Cancelar esta ordem de serviço?"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                            Cancelar
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Histórico da execução</h2>
            <p class="mt-0.5 text-xs text-slate-500">Registro permanente dos eventos da OS (somente acréscimo).</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($this->events as $event)
                <div class="flex flex-wrap items-start justify-between gap-4 px-6 py-4">
                    <div class="min-w-0">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $event->type->badgeClasses() }}">
                            {{ $event->type->label() }}
                        </span>
                        @if ($event->notes)
                            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $event->notes }}</p>
                        @endif
                    </div>
                    <div class="flex-none text-right">
                        <p class="text-sm text-slate-600">{{ $event->user?->name ?? 'Usuário removido' }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $event->created_at->format('d/m/Y \à\s H:i') }}</p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-bold text-slate-700">Nenhum evento registrado ainda</p>
                    <p class="mt-1 text-sm text-slate-500">Quando a execução começar, o andamento aparecerá aqui.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>