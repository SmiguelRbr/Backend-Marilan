<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\FerramentaController;
use Illuminate\Support\Facades\Route;

// Rota de Login (Pública)
Route::post('/login', [AuthController::class, 'login']);

// Rotas Protegidas (Precisa enviar o Token no Header)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/retirar', [MovimentacaoController::class, 'retirar']);
    Route::post('/devolver', [MovimentacaoController::class, 'devolver']);
    Route::post('/trocar', [MovimentacaoController::class, 'trocar']);
    Route::get('/ferramentas', [FerramentaController::class, 'index']);
    // Rota EXCLUSIVA para almoxarifes (Relatórios)
    Route::middleware('role:almoxarife')->group(function () {
        Route::get('/relatorios/movimentacoes', [RelatorioController::class, 'gerar']);
    });
});