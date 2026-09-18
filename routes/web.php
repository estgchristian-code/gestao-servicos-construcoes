<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::livewire('/home', 'pages::home')->name('home');

    Route::livewire('/clientes', 'pages::clients.index')->name('clients.index');
    Route::livewire('/clientes/novo', 'pages::clients.create')->name('clients.create');
    Route::livewire('/clientes/{client}', 'pages::clients.show')->name('clients.show');
    Route::livewire('/clientes/{client}/editar', 'pages::clients.edit')->name('clients.edit');

    Route::livewire('/servicos', 'pages::services.index')->name('services.index');
    Route::livewire('/servicos/novo', 'pages::services.create')->name('services.create');
    Route::livewire('/servicos/{service}', 'pages::services.show')->name('services.show');
    Route::livewire('/servicos/{service}/editar', 'pages::services.edit')->name('services.edit');

    Route::livewire('/orcamentos', 'pages::budgets.index')->name('budgets.index');
    Route::livewire('/orcamentos/novo', 'pages::budgets.create')->name('budgets.create');
    Route::livewire('/orcamentos/{budget}', 'pages::budgets.show')->name('budgets.show');
    Route::livewire('/orcamentos/{budget}/editar', 'pages::budgets.edit')->name('budgets.edit');

    Route::livewire('/ordens-de-servico', 'pages::service-orders.index')->name('service-orders.index');
    Route::livewire('/ordens-de-servico/novo', 'pages::service-orders.create')->name('service-orders.create');
    Route::livewire('/ordens-de-servico/{order}', 'pages::service-orders.show')->name('service-orders.show');
    Route::livewire('/ordens-de-servico/{order}/editar', 'pages::service-orders.edit')->name('service-orders.edit');
});
