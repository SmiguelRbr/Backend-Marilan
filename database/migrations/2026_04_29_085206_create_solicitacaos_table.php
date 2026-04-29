<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de solicitações de retirada criadas pelo colaborador.
     *
     * Fluxo:
     *   1. Colaborador preenche crachá + crachá do almoxarife + ferramentas → POST /solicitacoes
     *   2. Almoxarife faz polling em GET /solicitacoes/pendentes (filtrado pelo seu crachá)
     *   3. Almoxarife aprova → POST /solicitacoes/{id}/aprovar → cria Movimentacao + notifica colaborador
     *   4. Almoxarife recusa → POST /solicitacoes/{id}/recusar → notifica colaborador
     */
    public function up(): void
    {
        Schema::create('solicitacoes', function (Blueprint $table) {
            $table->id();

            // Quem pediu
            $table->foreignId('colaborador_id')->constrained('users');

            // Para qual almoxarife foi direcionado (pelo crachá digitado no app)
            $table->foreignId('almoxarife_id')->constrained('users');

            // Lista de ferramentas em JSON: [{ "codigo": "50036280", "qtd": 1, "checklist": "REALIZADO", "observacao": "" }]
            $table->json('ferramentas');

            // Status: pendente → aprovada | recusada
            $table->enum('status', ['pendente', 'aprovada', 'recusada'])->default('pendente');

            // Motivo da recusa (opcional)
            $table->string('motivo_recusa')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes');
    }
};