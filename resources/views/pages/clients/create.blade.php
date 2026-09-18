<?php

use App\Models\Client;
use App\Rules\ValidDocument;
use App\Support\Documents;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component {
    public string $type = 'pf';

    public string $name = '';

    public string $document = '';

    public string $email = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $notes = '';

    public string $tags = '';

    public bool $active = true;

    public function save()
    {
        $this->authorize('create', Client::class);

        $this->document = Documents::normalize($this->document) ?? '';

        $data = $this->validate([
            'type' => ['required', 'string', Rule::in(['pf', 'pj'])],
            'name' => ['required', 'string', 'max:255'],
            'document' => [
                'required',
                'string',
                new ValidDocument($this->type),
                Rule::unique('clients', 'document')
                    ->where('company_id', auth()->user()->company_id)
                    ->whereNull('deleted_at'),
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

        $client = new Client([
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
        $client->company_id = auth()->user()->company_id;
        $client->save();

        session()->flash('status', 'Cliente cadastrado com sucesso.');

        return redirect()->route('clients.show', $client);
    }

    public function resetForm(): void
    {
        $this->reset('type', 'name', 'document', 'email', 'phone', 'whatsapp', 'notes', 'tags');
        $this->active = true;
        $this->resetValidation();
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
        <a href="{{ route('clients.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para clientes
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Novo cliente</h1>
        <p class="mt-1 text-sm text-slate-500">Cadastre um novo cliente da sua empresa.</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.client-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('clients.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Cadastrar cliente
            </button>
        </div>
    </form>
</div>