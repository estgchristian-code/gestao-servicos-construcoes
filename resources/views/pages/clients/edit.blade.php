<?php

use App\Models\Client;
use App\Rules\ValidDocument;
use App\Support\Documents;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component {
    public Client $client;

    public string $type = 'pf';

    public string $name = '';

    public string $document = '';

    public string $email = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $notes = '';

    public string $tags = '';

    public bool $active = true;

    public function mount(Client $client): void
    {
        $this->client = $client;

        $this->authorize('update', $client);

        $this->type = $client->type->value;
        $this->name = $client->name;
        $this->document = Documents::format($client->document) ?? '';
        $this->email = $client->email ?? '';
        $this->phone = $client->phone ?? '';
        $this->whatsapp = $client->whatsapp ?? '';
        $this->notes = $client->notes ?? '';
        $this->tags = collect($client->tags ?? [])->implode(', ');
        $this->active = $client->active;
    }

    public function save()
    {
        $this->authorize('update', $this->client);

        $this->document = Documents::normalize($this->document) ?? '';

        $data = $this->validate([
            'type' => ['required', 'string', Rule::in(['pf', 'pj'])],
            'name' => ['required', 'string', 'max:255'],
            'document' => [
                'required',
                'string',
                new ValidDocument($this->type),
                Rule::unique('clients', 'document')
                    ->where('company_id', $this->client->company_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->client->getKey()),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
        ], [
            'name.required' => 'Informe o nome do cliente.',
            'document.required' => 'Informe o CPF ou CNPJ.',
            'document.unique' => 'Já existe um cliente com este CPF/CNPJ nesta empresa.',
            'type.in' => 'Selecione um tipo de pessoa válido.',
        ]);

        $this->client->update([
            'type' => $this->type,
            'name' => trim($this->name),
            'document' => $this->document !== '' ? $this->document : null,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'whatsapp' => $this->whatsapp ?: null,
            'notes' => $this->notes ?: null,
            'tags' => $this->normalizeTags() ?: null,
            'active' => $this->active,
        ]);

        session()->flash('status', 'Cliente atualizado com sucesso.');

        return redirect()->route('clients.show', $this->client);
    }

    protected function normalizeTags(): array
    {
        return collect(explode(',', $this->tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('clients.show', $this->client) }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para o cliente
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Editar cliente</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $this->client->name }}</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.client-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('clients.show', $this->client) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Salvar alterações
            </button>
        </div>
    </form>
</div>