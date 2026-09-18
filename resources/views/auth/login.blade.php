<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Entrar') }} — {{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 flex items-center justify-center p-6 antialiased">
        <div class="w-full max-w-sm">
            <h1 class="text-2xl font-semibold text-slate-800 mb-1">{{ config('app.name') }}</h1>
            <p class="text-sm text-slate-500 mb-6">Acesse com o e-mail e a senha da sua conta.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                @csrf

                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <label class="block mb-4">
                    <span class="text-sm font-medium text-slate-700">E-mail</span>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>

                <label class="block mb-4">
                    <span class="text-sm font-medium text-slate-700">Senha</span>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>

                <label class="flex items-center gap-2 mb-6 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    <span>Lembrar-me</span>
                </label>

                <button type="submit" class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition">
                    Entrar
                </button>
            </form>
        </div>
    </body>
</html>