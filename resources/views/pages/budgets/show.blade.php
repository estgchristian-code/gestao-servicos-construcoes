<?php

use App\Enums\BudgetStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\Budget;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    public Budget $budget;

    public function mount(Budget $budget): void
    {
        $this->budget = $budget;

        $this->authorize('view', $this->budget);
    }

    public function delete()
    {
        $this->authorize('delete', $this->budget);

        $this->budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('status', 'Orçamento excluído com sucesso.');
    }

    public function convert()
    {
        $this->authorize('convert', $this->budget);

        if ($this->budget->status !== BudgetStatus::Approved) {
            abort(403, 'Somente orçamentos aprovados podem ser convertidos em ordem de serviço.');
        }

        if ($this->budget->service_order_id !== null) {
            abort(403, 'Este orçamento já foi convertido em uma ordem de serviço.');
        }

        $order = DB::transaction(function () {
            $budget = Budget::query()
                ->whereKey($this->budget->id)
                ->lockForUpdate()
                ->first();

            if ($budget->status !== BudgetStatus::Approved || $budget->service_order_id !== null) {
                abort(403, 'Este orçamento não pode mais ser convertido em ordem de serviço.');
            }

            $order = new ServiceOrder([
                'client_id' => $budget->client_id,
                'budget_id' => $budget->id,
                'number' => $this->generateOrderNumber(),
                'title' => (string) $budget->title,
                'status' => ServiceOrderStatus::Pending,
                'scheduled_at' => null,
                'notes' => $budget->notes,
            ]);
            $order->company_id = $budget->company_id;
            $order->save();

            $total = 0.0;

            foreach ($budget->items as $item) {
                $subtotal = round((float) $item->subtotal, 2);
                $total += $subtotal;

                $orderItem = new ServiceOrderItem([
                    'service_order_id' => $order->id,
                    'service_id' => $item->service_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $subtotal,
                ]);
                $orderItem->company_id = $budget->company_id;
                $orderItem->save();
            }

            $order->update(['total' => round($total, 2)]);

            $budget->service_order_id = $order->id;
            $budget->save();

            return $order;
        });

        $this->budget = $this->budget->fresh();

        session()->flash('status', 'Orçamento convertido em OS #' . $order->number . ' com sucesso.');
    }

    private function generateOrderNumber(): string
    {
        $max = ServiceOrder::query()->forCompany($this->budget->company_id)->max('number');

        $next = $max !== null ? ((int) $max) + 1 : 1;

        return str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
};
?>
<div>
    @include('partials.flash')

    <div class="mb-6">
        <a href="{{ route('budgets.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para orçamentos
        </a>
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Orçamento #{{ $this->budget->number }}</h1>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $this->budget->status->badgeClasses() }}">
                    {{ $this->budget->status->label() }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Criado em {{ $this->budget->created_at->format('d/m/Y') }}</p>
        </div>

        @can('update', $this->budget)
            <div class="flex flex-none flex-wrap items-center gap-2">
                @if ($this->budget->status === BudgetStatus::Approved && $this->budget->service_order_id === null)
                    <button type="button" wire:click="convert" wire:confirm="Converter este orçamento em uma ordem de serviço? Esta ação não pode ser desfeita."
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        Converter em OS
                    </button>
                @endif

                <a href="{{ route('budgets.edit', $this->budget) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Editar
                </a>

                @can('delete', $this->budget)
                    <button type="button" wire:click="delete" wire:confirm="Excluir este orçamento? Esta ação não pode ser desfeita."
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Excluir
                    </button>
                @endcan
            </div>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            @if ($this->budget->serviceOrder)
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Ordem de serviço gerada</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Este orçamento foi convertido na ordem de serviço abaixo.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <a href="{{ route('service-orders.show', $this->budget->serviceOrder) }}"
                            class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-700">
                            OS #{{ $this->budget->serviceOrder->number }} — {{ $this->budget->serviceOrder->title }}
                        </a>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $this->budget->serviceOrder->status->badgeClasses() }}">
                            {{ $this->budget->serviceOrder->status->label() }}
                        </span>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Informações do orçamento</h2>
                </div>
                <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</p>
                        <a href="{{ route('clients.show', $this->budget->client) }}"
                            class="mt-1 block text-sm font-medium text-indigo-600 hover:text-indigo-700">
                            {{ $this->budget->client->name }}
                        </a>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Valor total</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">
                            R$ {{ number_format((float) $this->budget->total, 2, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Validade</p>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ $this->budget->valid_until?->format('d/m/Y') ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Atualizado em</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $this->budget->updated_at->format('d/m/Y \à\s H:i') }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Título / descrição</p>
                        <p class="mt-1 text-sm leading-relaxed text-slate-700">{{ $this->budget->title }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observações</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">
                            {{ $this->budget->notes ?? 'Sem observações cadastradas.' }}
                        </p>
                    </div>
                </div>
            </div>

            <livewire:manage-budget-items :budget="$this->budget" :key="$this->budget->id" />
        </div>
    </div>
</div>