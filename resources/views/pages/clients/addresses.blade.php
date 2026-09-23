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
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Endereços</h1>
            <p class="mt-1 text-sm text-slate-500">Cliente: {{ $this->client->name }}</p>
        </div>

        <a href="{{ route('clients.show', $this->client) }}"
            class="inline-flex flex-none items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
            Ver ficha
        </a>
    </div>

    <livewire:manage-client-addresses :client="$this->client" :key="$this->client->id" />
</div>