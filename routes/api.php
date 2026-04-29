<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FerramentaController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SolicitacaoController;
use Illuminate\Support\Facades\Route;

// ─── Rota de Login (Pública) ──────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Rotas semi-públicas (leitura) ────────────────────────────────────────────
Route::get('/ferramentas', [FerramentaController::class, 'index']);
Route::get('/relatorios/movimentacoes', [RelatorioController::class, 'gerar']);

// ─── Rotas Protegidas (Bearer Token obrigatório) ──────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {
    // Colaborador consulta o status da sua solicitação
    Route::get('/solicitacoes/{id}/status', [SolicitacaoController::class, 'status']);

    // Movimentações — só almoxarife pode retirar/devolver/trocar diretamente
    Route::post('/retirar', [MovimentacaoController::class, 'retirar']);
    Route::post('/devolver', [MovimentacaoController::class, 'devolver']);
    Route::post('/trocar',   [MovimentacaoController::class, 'trocar']);

    // ── Solicitações de retirada (fluxo colaborador → almoxarife) ─────────────
    //
    // Qualquer usuário autenticado pode CRIAR uma solicitação.
    // Apenas almoxarifes podem ver as pendentes, aprovar ou recusar.

    // Colaborador cria a solicitação
    Route::post('/solicitacoes', [SolicitacaoController::class, 'criar']);

    // Almoxarife lista solicitações pendentes direcionadas a ele (polling do app)
    Route::get('/solicitacoes/pendentes', [SolicitacaoController::class, 'pendentes'])
        ->middleware('role:almoxarife');

    // Almoxarife aprova ou recusa
    Route::post('/solicitacoes/{id}/aprovar', [SolicitacaoController::class, 'aprovar'])
        ->middleware('role:almoxarife');

    Route::post('/solicitacoes/{id}/recusar', [SolicitacaoController::class, 'recusar'])
        ->middleware('role:almoxarife');

    // ── Notificações ──────────────────────────────────────────────────────────
    Route::get('/notificacoes',                 [NotificacaoController::class, 'index']);
    Route::patch('/notificacoes/{id}/ler',      [NotificacaoController::class, 'marcarComoLida']);
    Route::post('/notificacoes/ler-tudo',       [NotificacaoController::class, 'lerTudo']);

    // ── Lookup por NFC ────────────────────────────────────────────────────────
    Route::get('/usuario/por-nfc/{nfc_id}', function ($nfc_id) {
        $user = \App\Models\User::where('nfc_id', $nfc_id)->firstOrFail();
        return response()->json([
            'cracha' => $user->cracha,
            'nome'   => $user->nome,
            'role'   => $user->role,
        ]);
    });

    // ── Exclusivo almoxarife ──────────────────────────────────────────────────
    Route::middleware('role:almoxarife')->group(function () {
        // Reservado para futuras rotas de gestão
    });


    // Fluxo de Devolução
    Route::post('/devolucao/solicitar', [MovimentacaoController::class, 'solicitarDevolucao']); // App do Colaborador
    Route::post('/devolucao/{id}/confirmar', [MovimentacaoController::class, 'confirmarDevolucao'])->middleware('role:almoxarife'); // App do Almoxarife (Aceita)
    Route::post('/devolucao/{id}/recusar', [MovimentacaoController::class, 'recusarDevolucao'])->middleware('role:almoxarife'); // App do Almoxarife (Rollback)
    Route::get('/devolucao/pendentes', [MovimentacaoController::class, 'pendentes'])->middleware('role:almoxarife');

});
