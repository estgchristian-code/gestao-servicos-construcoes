<?php

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function toggleActive(User $user): void
    {
        $this->authorize('update', $user);

        if ($user->is(auth()->user()) && $user->active) {
            session()->flash('error', 'Você não pode desativar a sua própria conta.');

            return;
        }

        $user->update(['active' => ! $user->active]);

        session()->flash(
            'status',
            $user->active ? 'Técnico ativado com sucesso.' : 'Técnico desativado com sucesso.'
        );
    }

    #[Computed]
    public function technicians()
    {
        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->where('roles.slug', UserRole::Tecnico->value))
            ->orderBy('users.name');

        if (! auth()->user()->isSuperAdmin()) {
            $query->where('users.company_id', auth()->user()->company_id);
        }

        return $query->paginate(15);
    }
};
?>
<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Técnicos</h1>
            <p class="mt-1 text-sm text-slate-500">Gerencie os técnicos da sua empresa.</p>
        </div>

        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}"
                class="inline-flex flex-none items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                </svg>
                <span>Novo técnico</span>
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Técnico</th>
                        <th scope="col" class="hidden px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 md:table-cell">E-mail</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->technicians as $technician)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($technician->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="block truncate font-medium text-slate-900">{{ $technician->name }}</p>
                                        @if (! auth()->user()->isSuperAdmin())
                                            <p class="truncate text-xs text-slate-500">{{ $technician->company?->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-6 py-4 md:table-cell">
                                <p class="truncate text-slate-700">{{ $technician->email }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if ($technician->active)
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
                                    @can('update', $technician)
                                        <a href="{{ route('users.edit', $technician) }}"
                                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                                            Editar
                                        </a>
                                        @if ($technician->active)
                                            <button type="button" wire:click="toggleActive({{ $technician->id }})"
                                                wire:confirm="Desativar o técnico? Ele não poderá mais acessar o sistema."
                                                class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                                                Desativar
                                            </button>
                                        @else
                                            <button type="button" wire:click="toggleActive({{ $technician->id }})"
                                                wire:confirm="Reativar o técnico?"
                                                class="rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-600 transition hover:bg-emerald-50">
                                                Ativar
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <p class="text-sm font-medium text-slate-700">Nenhum técnico encontrado</p>
                                <p class="mt-1 text-sm text-slate-500">Comece cadastrando um técnico da sua empresa.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->technicians->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $this->technicians->links() }}
            </div>
        @endif
    </div>
</div>