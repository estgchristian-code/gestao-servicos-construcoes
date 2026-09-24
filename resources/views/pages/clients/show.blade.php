<?php

use App\Models\Client;
use Livewire\Component;

new class extends Component {
    public Client $client;

    public function mount(Client $client): void
    {
        $this->client = $client->loadMissing('addresses');

        $this->authorize('view', $this->client);
    }

    public function toggleActive(): void
    {
        $this->authorize('update', $this->client);

        $this->client->update(['active' => ! $this->client->active]);

        session()->flash('status', $this->client->active
            ? 'Cliente ativado.'
            : 'Cliente desativado.');
    }

    public function delete()
    {
        $this->authorize('delete', $this->client);

        $this->client->delete();

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente excluído com sucesso.');
    }
};
?>
<div>
    @include('partials.flash')

    <div class="mb-6">
        <a href="{{ route('clients.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para clientes
        </a>
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 flex-none items-center justify-center rounded-2xl bg-indigo-100 text-xl font-semibold text-indigo-700 ring-1 ring-indigo-200">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($this->client->name, 0, 1)) }}
            </span>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $this->client->name }}</h1>
                    @if ($this->client->active)
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
                <p class="mt-0.5 text-sm text-slate-500">Cadastrado em {{ $this->client->created_at->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="flex flex-none flex-wrap items-center gap-2">
            <button type="button" wire:click="toggleActive"
                class="inline-flex items-center justify-center gap-2 rounded-lg border {{ $this->client->active ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50' }} px-4 py-2.5 text-sm font-medium shadow-sm transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                {{ $this->client->active ? 'Desativar' : 'Ativar' }}
            </button>

            <button type="button" wire:click="delete" wire:confirm="Excluir este cliente? Esta ação não pode ser desfeita."
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Excluir
            </button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Informações</h2>
                </div>
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-4 px-6 py-3.5">
                        <dt class="text-slate-500">Tipo</dt>
                        <dd class="font-medium text-slate-900">{{ $this->client->type->label() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-6 py-3.5">
                        <dt class="text-slate-500">Documento</dt>
                        <dd class="font-mono font-medium text-slate-900">{{ \App\Support\Documents::format($this->client->document) ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-6 py-3.5">
                        <dt class="text-slate-500">E-mail</dt>
                        <dd class="font-medium text-slate-900">{{ $this->client->email ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-6 py-3.5">
                        <dt class="text-slate-500">Telefone</dt>
                        <dd class="font-medium text-slate-900">{{ $this->client->phone ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-6 py-3.5">
                        <dt class="text-slate-500">WhatsApp</dt>
                        <dd class="font-medium text-slate-900">{{ $this->client->whatsapp ?? '—' }}</dd>
                    </div>
                    <div class="px-6 py-3.5">
                        <dt class="text-slate-500">Tags</dt>
                        <dd class="mt-1.5">
                            @forelse ($this->client->tags ?? [] as $tag)
                                <span class="mr-1.5 inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">{{ $tag }}</span>
                            @empty
                                <span class="text-slate-400">—</span>
                            @endforelse
                        </dd>
                    </div>
                </dl>
            </div>

            @if ($this->client->notes !== null && $this->client->notes !== '')
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Observações</h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $this->client->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div id="enderecos" class="lg:col-span-2">
            <livewire:manage-client-addresses :client="$this->client" :read-only="true" :key="$this->client->id" />
        </div>
    </div>
</div>