<div class="space-y-5">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Dados do orçamento</h2>
        </div>
        <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="client_id" class="mb-1 block text-sm font-medium text-slate-700">Cliente</label>
                <select id="client_id" wire:model="client_id"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">Selecione um cliente</option>
                    @foreach ($this->clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
                @error('client_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="number" class="mb-1 block text-sm font-medium text-slate-700">Número</label>
                <input id="number" type="text" value="{{ $number }}" disabled readonly
                    class="block w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Número sequencial gerado automaticamente.</p>
            </div>

            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Título / descrição</label>
                <input id="title" type="text" wire:model="title" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Ex.: Instalação de ar-condicionado">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                @if (isset($this->budget) && count($this->statusOptions) > 1)
                    <select id="status" wire:model="status"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                        @foreach ($this->statusOptions as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @else
                    <input id="status" type="text"
                        value="{{ isset($this->budget) ? $this->budget->status->label() : App\Enums\BudgetStatus::Draft->label() }}"
                        disabled readonly
                        class="block w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                @endif
            </div>

            <div>
                <label for="total" class="mb-1 block text-sm font-medium text-slate-700">Valor total (R$)</label>
                <input id="total" type="text" inputmode="decimal" wire:model="total" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Ex.: 1500,00">
                @error('total')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="valid_until" class="mb-1 block text-sm font-medium text-slate-700">Validade</label>
                <input id="valid_until" type="date" wire:model="valid_until"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                <p class="mt-1 text-xs text-slate-400">Opcional.</p>
                @error('valid_until')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                <textarea id="notes" wire:model="notes" rows="3"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Condições de pagamento, prazos, observações gerais..."></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>