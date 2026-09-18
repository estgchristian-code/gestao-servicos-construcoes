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
});
