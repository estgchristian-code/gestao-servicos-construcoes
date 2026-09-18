<div class="space-y-5">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Tipo de pessoa</h2>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="cursor-pointer">
                    <input type="radio" wire:model.live="type" value="pf" class="sr-only">
                    <span class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-medium transition {{ $type === 'pf' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400' }}">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $type === 'pf' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </span>
                        <span class="leading-tight">
                            <span class="block font-semibold">Pessoa Física</span>
                            <span class="block text-xs text-slate-500">CPF</span>
                        </span>
                    </span>
                </label>

                <label class="cursor-pointer">
                    <input type="radio" wire:model.live="type" value="pj" class="sr-only">
                    <span class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-medium transition {{ $type === 'pj' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400' }}">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $type === 'pj' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h3a.375.375 0 0 1 .375.375v3.75" />
                            </svg>
                        </span>
                        <span class="leading-tight">
                            <span class="block font-semibold">Pessoa Jurídica</span>
                            <span class="block text-xs text-slate-500">CNPJ</span>
                        </span>
                    </span>
                </label>
            </div>

            @error('type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Dados cadastrais</h2>
        </div>
        <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">
                    {{ $type === 'pj' ? 'Razão social / Nome fantasia' : 'Nome completo' }}
                </label>
                <input id="name" type="text" wire:model="name" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="{{ $type === 'pj' ? 'Ex.: Tech Serviços LTDA' : 'Ex.: Ana Paula Souza' }}">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="document" class="mb-1 block text-sm font-medium text-slate-700">
                    {{ $type === 'pj' ? 'CNPJ' : 'CPF' }}
                </label>
                <input id="document" type="text" wire:model="document" autocomplete="off" inputmode="numeric"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="{{ $type === 'pj' ? '00.000.000/0000-00' : '000.000.000-00' }}">
                <p class="mt-1 text-xs text-slate-400">Preencha apenas com os números. Pode colar com pontos e traços.</p>
                @error('document')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                <input id="email" type="email" wire:model="email" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="contato@exemplo.com">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tags" class="mb-1 block text-sm font-medium text-slate-700">Tags</label>
                <input id="tags" type="text" wire:model="tags" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="vip, recorrente, indicado">
                <p class="mt-1 text-xs text-slate-400">Separe por vírgula.</p>
                @error('tags')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                <input id="phone" type="text" wire:model="phone" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="(11) 99999-9999">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="whatsapp" class="mb-1 block text-sm font-medium text-slate-700">WhatsApp</label>
                <input id="whatsapp" type="text" wire:model="whatsapp" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="(11) 98888-7777">
                @error('whatsapp')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                <textarea id="notes" wire:model="notes" rows="4"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Informações adicionais sobre o cliente..."></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Cliente ativo</span>
                        <span class="block text-xs text-slate-500">Clientes inativos não aparecem por padrão na listagem.</span>
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