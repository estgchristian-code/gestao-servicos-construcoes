<?php

use Livewire\Component;

new class extends Component {
};
?>
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Início</h1>
        <p class="mt-1 text-sm text-slate-500">Visão geral da sua conta e da sua empresa.</p>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-6 sm:px-8">
            <h2 class="text-lg font-semibold text-slate-900">Bem-vindo, {{ auth()->user()?->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Este é o painel base da plataforma. Os módulos de gestão serão conectados aqui nas próximas etapas.</p>
        </div>

        @php
            $me = auth()->user();
            $meCompany = $me?->company;
            $infoRows = [
                'Nome' => $me?->name,
                'E-mail' => $me?->email,
                'Empresa' => $meCompany?->name ?? '—',
                'Perfil' => $me?->roleLabel() ?? '—',
            ];
        @endphp

        <dl class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x">
            @foreach ($infoRows as $label => $value)
                <div class="px-6 py-4 sm:px-8">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                    <dd class="mt-1 truncate text-sm font-medium text-slate-800">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="mt-6 flex items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
        <svg class="mt-0.5 h-5 w-5 flex-none text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <p class="text-sm text-indigo-900">
            Os itens do menu <span class="font-semibold">Serviços, Orçamentos, Ordens de Serviço e Agenda</span>
            estarão disponíveis em breve. O módulo de <span class="font-semibold">Clientes</span> já está ativo no menu.
        </p>
    </div>
</div>