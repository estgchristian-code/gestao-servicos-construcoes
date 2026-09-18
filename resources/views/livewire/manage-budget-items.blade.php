<?php

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Service;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Budget $budget;

    public bool $showForm = false;

    public ?int $editingItemId = null;

    public string $item_service_id = '';

    public string $item_description = '';

    public string $item_quantity = '1';

    public string $item_unit_price = '';

    public function mount(Budget $budget): void
    {
        $this->budget = $budget;

        $this->authorize('view', $this->budget);
    }

    #[Computed]
    public function items()
    {
        return $this->budget->items()->with('service')->orderBy('id')->get();
    }

    #[Computed]
    public function services()
    {
        return Service::query()
            ->forCompany($this->budget->company_id)
            ->where('services.active', true)
            ->orderBy('services.name')
            ->get();
    }

    #[Computed]
    public function total()
    {
        return round((float) $this->items()->sum(fn ($item) => (float) $item->subtotal), 2);
    }

    public function add(): void
    {
        $this->authorize('update', $this->budget);

        if ($this->editingItemId === null) {
            $this->resetItemForm();
        }
        $this->editingItemId = null;
        $this->showForm = true;
    }

    public function edit(int $itemId): void
    {
        $this->authorize('update', $this->budget);

        $item = $this->budget->items()->findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->item_service_id = (string) $item->service_id;
        $this->item_description = $item->description;
        $this->item_quantity = $this->formatDecimal($item->quantity);
        $this->item_unit_price = number_format((float) $item->unit_price, 2, ',', '');
        $this->showForm = true;
    }

    public function updatedItemService(string $value): void
    {
        if ($this->editingItemId !== null || trim($this->item_description) !== '') {
            return;
        }

        $service = Service::query()
            ->forCompany($this->budget->company_id)
            ->where('services.active', true)
            ->find($value);

        if ($service !== null) {
            $this->item_description = $service->name;
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->budget);

        $this->validate([
            'item_service_id' => [
                'required',
                Rule::exists('services', 'id')
                    ->where('company_id', $this->budget->company_id)
                    ->where('active', true),
            ],
            'item_description' => ['nullable', 'string', 'max:255'],
            'item_quantity' => [
                'required',
                'regex:/^\d{1,5}([.,]\d{1,2})?$/',
                function (string $attribute, $value, $fail) {
                    if ((float) $this->normalizeDecimal($value) <= 0) {
                        $fail('A quantidade deve ser maior que zero.');
                    }
                },
            ],
            'item_unit_price' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/', 'max:14'],
        ], [
            'item_service_id.required' => 'Selecione o serviço.',
            'item_service_id.exists' => 'O serviço selecionado é inválido.',
            'item_quantity.required' => 'Informe a quantidade.',
            'item_quantity.regex' => 'Informe uma quantidade válida (ex.: 1 ou 1,5).',
            'item_unit_price.required' => 'Informe o valor unitário.',
            'item_unit_price.regex' => 'Informe um valor válido (ex.: 150 ou 150,50).',
        ]);

        $quantity = $this->normalizeDecimal($this->item_quantity);
        $unitPrice = $this->normalizeDecimal($this->item_unit_price);
        $subtotal = round((float) $quantity * (float) $unitPrice, 2);

        $service = Service::query()
            ->forCompany($this->budget->company_id)
            ->where('services.active', true)
            ->find($this->item_service_id);

        $description = trim($this->item_description) !== ''
            ? trim($this->item_description)
            : ($service?->name ?? '');

        if ($this->editingItemId !== null) {
            $item = $this->budget->items()
                ->where('budget_items.company_id', $this->budget->company_id)
                ->findOrFail($this->editingItemId);
            $item->update([
                'service_id' => $this->item_service_id,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);
            session()->flash('status', 'Item atualizado com sucesso.');
        } else {
            $item = new BudgetItem([
                'service_id' => $this->item_service_id,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);
            $item->budget_id = $this->budget->id;
            $item->company_id = $this->budget->company_id;
            $item->save();
            session()->flash('status', 'Item adicionado com sucesso.');
        }

        $this->recalculateBudgetTotal();
        $this->resetItemForm();
        unset($this->items, $this->total);
    }

    public function delete(int $itemId): void
    {
        $this->authorize('update', $this->budget);

        $item = $this->budget->items()->findOrFail($itemId);
        $item->delete();

        $this->recalculateBudgetTotal();
        unset($this->items, $this->total);
        session()->flash('status', 'Item excluído com sucesso.');
    }

    public function cancel(): void
    {
        $this->resetItemForm();
    }

    protected function recalculateBudgetTotal(): void
    {
        $total = $this->budget->items()->sum('subtotal');

        $this->budget->update(['total' => $total]);
    }

    protected function resetItemForm(): void
    {
        $this->reset('item_service_id', 'item_description', 'item_unit_price');
        $this->item_quantity = '1';
        $this->editingItemId = null;
        $this->showForm = false;
        $this->resetValidation();
    }

    protected function normalizeDecimal(string $value): string
    {
        return str_replace(',', '.', trim($value));
    }

    protected function formatDecimal(int|string|null $value): string
    {
        return number_format((float) $value, 2, ',', '');
    }
};
?>
<div>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Itens do orçamento</h2>
                <p class="mt-0.5 text-xs text-slate-500">Os itens definem o valor total do orçamento.</p>
            </div>

            @can('update', $this->budget)
                @if (! $showForm)
                    <button type="button" wire:click="add"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Adicionar item
                    </button>
                @endif
            @endcan
        </div>

        @if ($showForm)
            <form wire:submit="save" class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="item-service" class="mb-1 block text-sm font-medium text-slate-700">Serviço</label>
                    <select id="item-service" wire:model="item_service_id"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                        <option value="">Selecione um serviço</option>
                        @foreach ($this->services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                    @error('item_service_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="item-description" class="mb-1 block text-sm font-medium text-slate-700">Descrição</label>
                    <input id="item-description" type="text" wire:model="item_description" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="Descreva o item...">
                    @error('item_description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="item-quantity" class="mb-1 block text-sm font-medium text-slate-700">Quantidade</label>
                    <input id="item-quantity" type="text" inputmode="decimal" wire:model="item_quantity" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="1">
                    @error('item_quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="item-unit-price" class="mb-1 block text-sm font-medium text-slate-700">Valor unitário (R$)</label>
                    <input id="item-unit-price" type="text" inputmode="decimal" wire:model="item_unit_price" autocomplete="off"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="Ex.: 150,00">
                    @error('item_unit_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 sm:col-span-2 pt-1">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        {{ $editingItemId !== null ? 'Salvar item' : 'Adicionar item' }}
                    </button>
                    <button type="button" wire:click="cancel"
                        class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                        Cancelar
                    </button>
                </div>
            </form>
        @else
            <div class="divide-y divide-slate-100">
                @forelse ($this->items as $item)
                    <div class="flex flex-wrap items-start justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">{{ $item->description }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $item->service?->name ?? 'Serviço removido' }}
                                • Qtd. {{ number_format((float) $item->quantity, 2, ',', '.') }}
                            </p>
                        </div>
                        <div class="flex flex-none items-center gap-4">
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Unit.</p>
                                <p class="text-sm text-slate-600">R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Subtotal</p>
                                <p class="text-sm font-semibold text-slate-900">R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}</p>
                            </div>
                            @can('update', $this->budget)
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="edit({{ $item->id }})"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Excluir este item?"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                        Excluir
                                    </button>
                                </div>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-medium text-slate-700">Nenhum item cadastrado</p>
                        <p class="mt-1 text-sm text-slate-500">Adicione itens para definir o valor do orçamento.</p>
                        @can('update', $this->budget)
                            <button type="button" wire:click="add"
                                class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Adicionar item
                            </button>
                        @endcan
                    </div>
                @endforelse
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
            <p class="text-sm font-medium text-slate-600">Total</p>
            <p class="text-lg font-semibold text-slate-900">R$ {{ number_format($this->total, 2, ',', '.') }}</p>
        </div>
    </div>
</div>