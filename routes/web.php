<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RelatorioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LoginWebController;

Route::get('/login', [LoginWebController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginWebController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginWebController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:almoxarife'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/relatorios/exportar', [RelatorioController::class, 'gerar'])->name('relatorios.web.exportar');
    });