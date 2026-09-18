<?php

use App\Models\Service;
use Livewire\Component;

new class extends Component {
    public Service $service;

    public function mount(Service $service): void
    {
        $this->service = $service;

        $this->authorize('view', $this->service);
    }

    public function toggleActive(): void
    {
        $this->authorize('update', $this->service);

        $this->service->update(['active' => ! $this->service->active]);

        session()->flash('status', $this->service->active
            ? 'Serviço ativado.'
            : 'Serviço desativado.');
    }

    public function delete()
    {
        $this->authorize('delete', $this->service);

        $this->service->delete();

        return redirect()
            ->route('services.index')
            ->with('status', 'Serviço excluído com sucesso.');
    }
};
?>
<div>
    @include('partials.flash')

    <div class="mb-6">
        <a href="{{ route('services.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para serviços
        </a>
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $this->service->name }}</h1>
                @if ($this->service->active)
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
            </div>
            <p class="mt-1 text-sm text-slate-500">Cadastrado em {{ $this->service->created_at->format('d/m/Y') }}</p>
        </div>

        @can('update', $this->service)
            <div class="flex flex-none flex-wrap items-center gap-2">
                <a href="{{ route('services.edit', $this->service) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Editar
                </a>

                <button type="button" wire:click="toggleActive"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border {{ $this->service->active ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50' }} px-4 py-2.5 text-sm font-medium shadow-sm transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    {{ $this->service->active ? 'Desativar' : 'Ativar' }}
                </button>

                @can('delete', $this->service)
                    <button type="button" wire:click="delete" wire:confirm="Excluir este serviço? Esta ação não pode ser desfeita."
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
        <div class="max-w-3xl overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Informações</h2>
            </div>
            <div class="px-6 py-4">
                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700">
                    {{ $this->service->description ?? 'Sem descrição cadastrada.' }}
                </p>
            </div>
        </div>
    </div>
</div>