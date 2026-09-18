<div class="space-y-5">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Dados do serviço</h2>
        </div>
        <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nome do serviço</label>
                <input id="name" type="text" wire:model="name" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Ex.: Manutenção preventiva">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Descrição</label>
                <textarea id="description" wire:model="description" rows="4"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Descreva o escopo e os detalhes do serviço..."></textarea>
                <p class="mt-1 text-xs text-slate-400">Opcional.</p>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Serviço ativo</span>
                        <span class="block text-xs text-slate-500">Serviços inativos não aparecem por padrão na listagem.</span>
                    </span>
                    <input type="checkbox" wire:model="active"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                </label>
                @error('active')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>