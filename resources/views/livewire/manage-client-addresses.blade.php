<?php

use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Client $client;

    public bool $readOnly = false;

    public bool $showForm = false;

    public ?int $editingAddressId = null;

    public string $label = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $district = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $reference = '';

    public bool $main = false;

    public function mount(Client $client, bool $readOnly = false): void
    {
        $this->client = $client;
        $this->readOnly = $readOnly;

        if (! $client->relationLoaded('addresses')) {
            $client->load('addresses');
        }

        $this->authorize($this->readOnly ? 'view' : 'update', $this->client);
    }

    #[Computed]
    public function addresses()
    {
        return $this->client->addresses()->orderBy('main', 'desc')->orderBy('created_at')->get();
    }

    public function add(): void
    {
        abort_if($this->readOnly, 403);

        $this->authorize('update', $this->client);

        if (! $this->editingAddressId) {
            $this->resetAddressForm();
        }
        $this->editingAddressId = null;
        $this->showForm = true;

        if ($this->addresses->isEmpty()) {
            $this->main = true;
        }
    }

    public function edit(ClientAddress $address): void
    {
        abort_if($this->readOnly, 403);

        $address = $this->client->addresses()->whereKey($address->id)->first();

        abort_if($address === null, 404, 'Endereço não encontrado.');

        $this->authorize('update', $address);

        $this->editingAddressId = $address->id;
        $this->label = $address->label;
        $this->street = $address->street;
        $this->number = $address->number;
        $this->complement = $address->complement ?? '';
        $this->district = $address->district ?? '';
        $this->city = $address->city;
        $this->state = $address->state;
        $this->zip = $address->zip;
        $this->reference = $address->reference ?? '';
        $this->main = $address->main;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_if($this->readOnly, 403);

        $this->authorize('update', $this->client);

        $data = $this->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:150'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['nullable', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'reference' => ['nullable', 'string', 'max:255'],
            'main' => ['boolean'],
        ], [
            'street.required' => 'Informe a rua.',
            'number.required' => 'Informe o número.',
            'city.required' => 'Informe a cidade.',
            'state.required' => 'Informe a UF.',
            'state.size' => 'Informe a UF com 2 letras.',
            'zip.regex' => 'CEP inválido.',
        ]);

        $attributes = [
            'label' => $this->label ?: null,
            'street' => trim($this->street),
            'number' => trim($this->number),
            'complement' => $this->complement ?: null,
            'district' => $this->district ?: null,
            'city' => trim($this->city),
            'state' => strtoupper(trim($this->state)),
            'zip' => $this->normalizeZip() ?: null,
            'reference' => $this->reference ?: null,
            'main' => $this->main,
        ];

        if ($this->editingAddressId !== null) {
            $address = $this->client->addresses()->findOrFail($this->editingAddressId);
            $this->authorize('update', $address);
            $address->update($attributes);
            session()->flash('status', 'Endereço atualizado com sucesso.');
        } else {
            $this->client->addresses()->create($attributes);
            session()->flash('status', 'Endereço adicionado com sucesso.');
        }

        $this->resetAddressForm();
        unset($this->addresses);
    }

    public function makeMain(ClientAddress $address): void
    {
        abort_if($this->readOnly, 403);

        $this->authorize('update', $this->client);

        $address = $this->client->addresses()->whereKey($address->id)->first();

        abort_if($address === null, 404, 'Endereço não encontrado.');

        $this->authorize('update', $address);

        $address->update(['main' => true]);
        unset($this->addresses);
        session()->flash('status', 'Endereço principal atualizado.');
    }

    public function delete(ClientAddress $address): void
    {
        abort_if($this->readOnly, 403);

        $this->authorize('delete', $address);

        $address->delete();
        unset($this->addresses);
        session()->flash('status', 'Endereço excluído com sucesso.');
    }

    public function cancel(): void
    {
        $this->resetAddressForm();
    }

    protected function resetAddressForm(): void
    {
        $this->reset('label', 'street', 'number', 'complement', 'district', 'city', 'state', 'zip', 'reference');
        $this->main = false;
        $this->editingAddressId = null;
        $this->showForm = false;
        $this->resetValidation();
    }

    protected function normalizeZip(): ?string
    {
        $digits = preg_replace('/\D/', '', $this->zip);

        return $digits !== '' && strlen($digits) === 8 ? $digits : null;
    }
};
?>
<div>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Endereços</h2>
                <p class="mt-0.5 text-xs text-slate-500">Apenas um endereço pode ser o principal.</p>
            </div>

            @if (! $showForm && ! $this->readOnly)
                <button type="button" wire:click="add"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Adicionar
                </button>
            @endif
        </div>

        @if ($showForm)
            <form wire:submit="save" class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <label for="addr-label" class="mb-1 block text-sm font-medium text-slate-700">Identificação</label>
                            <input id="addr-label" type="text" wire:model="label" autocomplete="off"
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                                placeholder="Casa, Trabalho...">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="addr-cep" class="mb-1 block text-sm font-medium text-slate-700">CEP</label>
                            <input id="addr-cep" type="text" wire:model="zip" autocomplete="off" inputmode="numeric"
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                                placeholder="00000-000">
                            @error('zip')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="addr-street" class="mb-1 block text-sm font-medium text-slate-700">Rua / Logradouro</label>
                    <input id="addr-street" type="text" wire:model="street" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="Av. Paulista">
                    @error('street')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="addr-number" class="mb-1 block text-sm font-medium text-slate-700">Número</label>
                    <input id="addr-number" type="text" wire:model="number" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="1000">
                    @error('number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="addr-complement" class="mb-1 block text-sm font-medium text-slate-700">Complemento</label>
                    <input id="addr-complement" type="text" wire:model="complement" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="Apto 42">
                </div>

                <div>
                    <label for="addr-district" class="mb-1 block text-sm font-medium text-slate-700">Bairro</label>
                    <input id="addr-district" type="text" wire:model="district" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="Jardins">
                </div>

                <div>
                    <label for="addr-city" class="mb-1 block text-sm font-medium text-slate-700">Cidade</label>
                    <input id="addr-city" type="text" wire:model="city" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="São Paulo">
                    @error('city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="addr-state" class="mb-1 block text-sm font-medium text-slate-700">UF</label>
                    <input id="addr-state" type="text" wire:model="state" maxlength="2" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="SP">
                    @error('state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="addr-reference" class="mb-1 block text-sm font-medium text-slate-700">Ponto de referência</label>
                    <input id="addr-reference" type="text" wire:model="reference" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="Próximo ao metrô...">
                </div>

                <div class="sm:col-span-2">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model="main"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-slate-700">Endereço principal</span>
                    </label>
                </div>

                <div class="flex items-center gap-3 sm:col-span-2 pt-1">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        {{ $editingAddressId ? 'Salvar endereço' : 'Adicionar endereço' }}
                    </button>
                    <button type="button" wire:click="cancel"
                        class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                        Cancelar
                    </button>
                </div>
            </form>
        @else
            <div class="divide-y divide-slate-100">
                @forelse ($this->addresses as $address)
                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full {{ $address->main ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-medium text-slate-900">{{ $address->label ?? 'Endereço' }}</p>
                                    @if ($address->main)
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">Principal</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-sm text-slate-600">
                                    {{ $address->street }}, {{ $address->number }}
                                    @if ($address->complement)
                                        — {{ $address->complement }}
                                    @endif
                                </p>
                                <p class="text-sm text-slate-500">
                                    {{ $address->district ? $address->district.', ' : '' }}{{ $address->city }} / {{ $address->state }}
                                    @if ($address->zip)
                                        • CEP {{ $address->zip }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if (! $this->readOnly)
                            <div class="flex flex-none items-center gap-1">
                                @if (! $address->main)
                                    <button type="button" wire:click="makeMain({{ $address->id }})" title="Definir como principal"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50">
                                        Principal
                                    </button>
                                @endif
                                <button type="button" wire:click="edit({{ $address->id }})"
                                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                                    Editar
                                </button>
                                <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Excluir este endereço?"
                                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                    Excluir
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-medium text-slate-700">Nenhum endereço cadastrado</p>
                        <p class="mt-1 text-sm text-slate-500">Adicione o primeiro endereço deste cliente.</p>
                        @if (! $this->readOnly)
                            <button type="button" wire:click="add"
                                class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Adicionar endereço
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>