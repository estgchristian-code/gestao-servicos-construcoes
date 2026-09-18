<div class="space-y-5">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Dados da ordem de serviço</h2>
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

            <div class="sm:col-span-2">
                <label for="budget_id" class="mb-1 block text-sm font-medium text-slate-700">Orçamento vinculado</label>
                <select id="budget_id" wire:model="budget_id"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">Sem orçamento vinculado</option>
                    @foreach ($this->budgets as $budget)
                        <option value="{{ $budget->id }}">#{{ $budget->number }} — {{ $budget->title }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Opcional. Somente orçamentos do cliente selecionado.</p>
                @error('budget_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                <select id="status" wire:model="status"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    @foreach (App\Enums\ServiceOrderStatus::cases() as $statusCase)
                        <option value="{{ $statusCase->value }}">{{ $statusCase->label() }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="technician_id" class="mb-1 block text-sm font-medium text-slate-700">Técnico responsável</label>
                <select id="technician_id" wire:model="technician_id"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">Sem técnico responsável</option>
                    @foreach ($this->technicians as $technician)
                        <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Opcional. Somente técnicos da sua empresa.</p>
                @error('technician_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="scheduled_at" class="mb-1 block text-sm font-medium text-slate-700">Data agendada</label>
                <input id="scheduled_at" type="date" wire:model="scheduled_at"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                <p class="mt-1 text-xs text-slate-400">Opcional.</p>
                @error('scheduled_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                <textarea id="notes" wire:model="notes" rows="3"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Detalhes, instruções, materiais, particularidades do local..."></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>