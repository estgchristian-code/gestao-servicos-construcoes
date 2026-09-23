<?php

use App\Models\Client;
use App\Support\Documents;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public string $status = 'active';

    protected $queryString = ['search', 'type', 'status'];

    public function mount(): void
    {
        $this->authorize('viewAny', Client::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'type', 'status');
        $this->resetPage();
    }

    #[Computed]
    public function clients()
    {
        $query = Client::query()
            ->with(['addresses' => fn ($q) => $q->where('main', true)])
            ->forCompany(auth()->user())
            ->orderBy('name');

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('clients.name', 'like', "%{$search}%")
                    ->orWhere('clients.document', 'like', "%{$search}%")
                    ->orWhere('clients.email', 'like', "%{$search}%");
            });
        }

        if ($this->type !== '') {
            $query->where('clients.type', $this->type);
        }

        if ($this->status === 'inactive') {
            $query->where('clients.active', false);
        } elseif ($this->status === 'all') {
            // no status restriction
        } else {
            $query->where('clients.active', true);
        }

        return $query->paginate(15);
    }
};
?>
<div>
    @include('partials.flash')

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Clientes</h1>
            <p class="mt-1 text-sm text-slate-500">Gerencie os clientes da sua empresa.</p>
        </div>

        <a href="{{ route('clients.create') }}"
            class="inline-flex flex-none items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>Novo cliente</span>
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-3 border-b border-slate-100 px-6 py-4 lg:grid-cols-3">
            <label class="relative block">
                <span class="sr-only">Buscar</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nome, documento ou e-mail"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
            </label>

            <select wire:model.live="type"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                <option value="">Todos os tipos</option>
                <option value="pf">Pessoa Física (PF)</option>
                <option value="pj">Pessoa Jurídica (PJ)</option>
            </select>

            <select wire:model.live="status"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                <option value="active">Somente ativos</option>
                <option value="inactive">Somente inativos</option>
                <option value="all">Todos</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Documento</th>
                        <th scope="col" class="hidden px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 md:table-cell">Contato</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->clients as $client)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($client->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('clients.show', $client) }}" class="block truncate font-medium text-slate-900 hover:text-indigo-600">
                                            {{ $client->name }}
                                        </a>
                                        <p class="truncate text-xs text-slate-500">
                                            {{ $client->type->label() }}
                                            @if ($client->addresses->isNotEmpty())
                                                • {{ $client->addresses->first()->city }}/{{ $client->addresses->first()->state }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-600">{{ Documents::format($client->document) ?? '—' }}</td>
                            <td class="hidden px-6 py-4 md:table-cell">
                                <p class="truncate text-slate-700">{{ $client->email ?? '—' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $client->phone ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if ($client->active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('clients.show', $client) }}"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50">
                                        Ver
                                    </a>
                                    <a href="{{ route('clients.addresses', $client) }}"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                                        Endereços
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <p class="text-sm font-medium text-slate-700">Nenhum cliente encontrado</p>
                                <p class="mt-1 text-sm text-slate-500">Ajuste a busca ou comece cadastrando um cliente.</p>
                                @if ($this->search !== '' || $this->type !== '' || $this->status !== 'active')
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

        @if ($this->clients->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $this->clients->links() }}
            </div>
        @endif
    </div>
</div>