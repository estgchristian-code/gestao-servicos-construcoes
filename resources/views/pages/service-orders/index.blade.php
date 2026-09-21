<?php

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    protected $queryString = ['search', 'status'];

    public function mount(): void
    {
        $this->authorize('viewAny', ServiceOrder::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        $query = ServiceOrder::query()
            ->with(['client'])
            ->forCompany(auth()->user())
            ->orderByDesc('service_orders.created_at');

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('service_orders.number', 'like', "%{$search}%")
                    ->orWhere('service_orders.title', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('clients.name', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->status !== 'all') {
            $query->where('service_orders.status', $this->status);
        }

        return $query->paginate(15);
    }
};
?>
<div>
    @include('partials.flash')

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Ordens de Serviço</h1>
            <p class="mt-1 text-sm text-slate-500">Gerencie as ordens de serviço dos seus clientes.</p>
        </div>

        @can('create', ServiceOrder::class)
            <a href="{{ route('service-orders.create') }}"
                class="inline-flex flex-none items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Nova ordem de serviço</span>
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-3 border-b border-slate-100 px-6 py-4 sm:grid-cols-2">
            <label class="relative block">
                <span class="sr-only">Buscar</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por número, título ou cliente"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
            </label>

            <select wire:model.live="status"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                <option value="all">Todos os status</option>
                @foreach (ServiceOrderStatus::cases() as $statusCase)
                    <option value="{{ $statusCase->value }}">{{ $statusCase->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Número</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</th>
                        <th scope="col" class="hidden px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 md:table-cell">Título</th>
                        <th scope="col" class="hidden px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Status</th>
                        <th scope="col" class="hidden px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 lg:table-cell">Agendada</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Valor</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->orders as $order)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <a href="{{ route('service-orders.show', $order) }}" class="block truncate font-medium text-slate-900 hover:text-indigo-600">
                                    #{{ $order->number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <p class="truncate text-slate-600">{{ $order->client?->name ?? '—' }}</p>
                            </td>
                            <td class="hidden px-6 py-4 md:table-cell">
                                <p class="truncate text-slate-600">{{ $order->title }}</p>
                            </td>
                            <td class="hidden px-6 py-4 sm:table-cell">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $order->status->badgeClasses() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="hidden px-6 py-4 lg:table-cell">
                                <p class="truncate text-slate-600">
                                    {{ $order->scheduled_at?->format('d/m/Y') ?? '—' }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-900">
                                R$ {{ number_format((float) $order->total, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('service-orders.show', $order) }}"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50">
                                        Ver
                                    </a>
                                    @can('update', $order)
                                        <a href="{{ route('service-orders.edit', $order) }}"
                                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                                            Editar
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <p class="text-sm font-medium text-slate-700">Nenhuma ordem de serviço encontrada</p>
                                <p class="mt-1 text-sm text-slate-500">Ajuste a busca ou comece criando uma ordem de serviço.</p>
                                @if ($this->search !== '' || $this->status !== 'all')
                                    <button type="button" wire:click="resetFilters"
                                        class="mt-4 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                        Limpar filtros
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->orders->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $this->orders->links() }}
            </div>
        @endif
    </div>
</div>