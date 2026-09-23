<div class="space-y-5">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Dados do técnico</h2>
        </div>
        <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nome completo</label>
                <input id="name" type="text" wire:model="name" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="Ex.: Carlos Eduardo Santos">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                <input id="email" type="email" wire:model="email" autocomplete="off"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    placeholder="tecnico@empresa.com">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">{{ $editing ? 'Nova senha' : 'Senha' }}</label>
                <input id="password" type="password" wire:model="password" autocomplete="new-password"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirmar senha</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <p class="text-xs text-slate-400">
                    {{ $editing ? 'Deixe a senha em branco para manter a atual.' : 'Use no mínimo 8 caracteres.' }}
                </p>
            </div>

            <div class="sm:col-span-2">
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Técnico ativo</span>
                        <span class="block text-xs text-slate-500">Técnicos inativos não conseguem acessar o sistema.</span>
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