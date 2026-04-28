<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\FerramentaController;
use App\Http\Controllers\NotificacaoController;
use Illuminate\Support\Facades\Route;

// Rota de Login (Pública)
Route::post('/login', [AuthController::class, 'login']);

Route::get('/ferramentas', [FerramentaController::class, 'index']);
Route::get('/relatorios/movimentacoes', [RelatorioController::class, 'gerar']);

// Rotas Protegidas (Precisa enviar o Token no Header)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/retirar', [MovimentacaoController::class, 'retirar']);
    Route::post('/devolver', [MovimentacaoController::class, 'devolver']);
    Route::post('/trocar', [MovimentacaoController::class, 'trocar']);

    Route::get('/notificacoes', [NotificacaoController::class, 'index']);
    Route::patch('/notificacoes/{id}/ler', [NotificacaoController::class, 'marcarComoLida']);
    Route::post('/notificacoes/ler-tudo', [NotificacaoController::class, 'lerTudo']);

    Route::get('/usuario/por-nfc/{nfc_id}', function ($nfc_id) {
        $user = \App\Models\User::where('nfc_id', $nfc_id)->firstOrFail();
        return response()->json(['cracha' => $user->cracha, 'nome' => $user->nome]);
    });
    // Rota EXCLUSIVA para almoxarifes (Relatórios)
    Route::middleware('role:almoxarife')->group(function () {});
});
