<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gestão de Serviços</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

@php
    $user = auth()->user();
    $company = $user?->company;

    $initials = collect(explode(' ', trim($user?->name ?? '')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
        ->implode('');

    $icons = [
        'home' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>',
        'users' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>',
        'wrench' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877m-1.703 1.797a2.25 2.25 0 1 0-3.184-3.184l-5.827 5.827a2.25 2.25 0 1 0 3.184 3.184l5.827-5.827m6.22-3.105a3.75 3.75 0 1 1-4.553-4.57l-2.668 2.668a.75.75 0 0 1-1.061 0L8.377 8.045a.75.75 0 0 1 0-1.06l2.669-2.669a3.75 3.75 0 1 1 4.57 4.552l2.668 2.668a.75.75 0 0 1 1.061 1.061l2.668 2.668Z"/></svg>',
        'bill' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>',
        'clipboard' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>',
        'calendar' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>',
        'user-plus' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/></svg>',
        'logout' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>',
    ];

    $navItems = [
        ['label' => 'Início', 'route' => 'home', 'icon' => 'home'],
        ['label' => 'Clientes', 'route' => 'clients.index', 'icon' => 'users'],
        ['label' => 'Serviços', 'route' => 'services.index', 'icon' => 'wrench'],
        ['label' => 'Orçamentos', 'route' => 'budgets.index', 'icon' => 'bill'],
        ['label' => 'Ordens de Serviço', 'route' => 'service-orders.index', 'icon' => 'clipboard'],
        ['label' => 'Agenda', 'route' => 'agenda', 'icon' => 'calendar'],
    ];

    if ($user !== null && $user->can('viewAny', \App\Models\User::class)) {
        array_splice($navItems, 2, 0, [
            ['label' => 'Técnicos', 'route' => 'users.index', 'icon' => 'user-plus'],
        ]);
    }
@endphp

<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">

        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" x-cloak x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"></div>

        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-50 flex w-64 max-w-[85vw] -translate-x-full flex-col bg-slate-900 shadow-2xl transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 lg:transition-none"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            {{-- Brand --}}
            <div class="flex h-16 flex-none items-center gap-3 border-b border-white/10 px-6">
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 text-white">
                    {!! $icons['wrench'] !!}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">{{ $company?->name ?? 'Gestão de Serviços' }}</p>
                    <p class="truncate text-xs text-slate-400">Serviços em campo</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Menu</p>

                @foreach ($navItems as $item)
                    @php
                        $itemRoute = $item['route'];
                        $itemPrefix = $itemRoute !== null ? Str::beforeLast($itemRoute, '.') : null;
                        $active = $itemRoute !== null
                            && (
                                request()->routeIs($itemRoute)
                                || ($itemPrefix !== null && request()->routeIs($itemPrefix.'.*'))
                            );
                        $iconClass = $active
                            ? 'text-indigo-400'
                            : ($itemRoute ? 'text-slate-500' : 'text-slate-600');
                    @endphp

                    @if ($itemRoute)
                        <a href="{{ route($item['route']) }}"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                                {{ $active ? 'bg-indigo-500/15 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                            {{ $active ? 'aria-current="page"' : '' }}>
                            <span class="{{ $iconClass }}">
                                {!! $icons[$item['icon']] !!}
                            </span>
                            <span class="flex-1">{{ $item['label'] }}</span>
                        </a>
                    @else
                        <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500"
                            aria-disabled="true" title="{{ $item['label'] }} estará disponível em breve">
                            <span class="{{ $iconClass }}">
                                {!! $icons[$item['icon']] !!}
                            </span>
                            <span class="flex-1 text-left">{{ $item['label'] }}</span>
                            <span class="rounded-full bg-white/5 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-white/10">Em breve</span>
                        </span>
                    @endif
                @endforeach
            </nav>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Topbar --}}
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" @click="sidebarOpen = true"
                    class="-ml-1 inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
                    aria-label="Abrir menu" aria-haspopup="true" aria-expanded="false">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold leading-tight text-slate-900">{{ $company?->name ?? 'Gestão de Serviços' }}</p>
                    <p class="truncate text-xs leading-tight text-slate-500">Sistema de gestão de serviços</p>
                </div>

                <div class="ml-auto flex flex-none items-center gap-2 sm:gap-3">
                    <div class="hidden min-w-0 text-right sm:block">
                        <p class="max-w-[180px] truncate text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                        <p class="max-w-[180px] truncate text-xs text-slate-500">{{ $user?->roleLabel() ?? 'Perfil' }}</p>
                    </div>

                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200"
                        title="{{ $user?->name }}">{{ $initials ?: 'U' }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 sm:px-3.5"
                            aria-label="Sair da conta">
                            {!! $icons['logout'] !!}
                            <span class="hidden sm:inline">Sair</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                @include('partials.flash')
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>

</html>