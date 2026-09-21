<?php

use App\Models\ServiceOrder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ServiceOrder $order;

    public function mount(ServiceOrder $order): void
    {
        $this->order = $order;

        $this->authorize('view', $this->order);
    }

    #[Computed]
    public function histories()
    {
        return $this->order->histories()->with('user')->get();
    }

    #[On('order-status-updated')]
    #[On('attachment-uploaded')]
    public function refreshHistories(): void
    {
        unset($this->histories);
    }
};
?>
<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="border-b border-slate-100 px-6 py-4">
        <h2 class="text-sm font-semibold text-slate-900">Histórico da ordem de serviço</h2>
        <p class="mt-0.5 text-xs text-slate-500">Principais ações realizadas nesta OS, do mais recente para o mais antigo.</p>
    </div>

    <ol class="space-y-0">
        @forelse ($this->histories as $history)
            <li class="flex items-start gap-4 border-b border-slate-100 px-6 py-4 last:border-b-0">
                <div class="mt-0.5 flex flex-none flex-col items-center">
                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $history->type->badgeClasses() }}">
                            {{ $history->type->label() }}
                        </span>
                        <p class="text-sm leading-relaxed text-slate-700">{{ $history->description }}</p>
                    </div>
                </div>

                <div class="flex-none text-right">
                    <p class="text-sm text-slate-600">{{ $history->user?->name ?? 'Usuário removido' }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ $history->created_at->format('d/m/Y \à\s H:i') }}</p>
                </div>
            </li>
        @empty
            <li class="px-6 py-12 text-center">
                <p class="text-sm font-bold text-slate-700">Nenhum evento registrado ainda</p>
                <p class="mt-1 text-sm text-slate-500">As ações realizadas nesta OS aparecerão aqui em ordem cronológica.</p>
            </li>
        @endforelse
    </ol>
</div>