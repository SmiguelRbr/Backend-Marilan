<?php

use App\Http\Controllers\NotificacaoController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Agendamentos de Notificações ─────────────────────────────────────────────

/**
 * Fim do 1º Turno (~22h)
 * Verifica ferramentas abertas há mais de 8h e alerta colaboradores/almoxarifes.
 */
Schedule::command('notificar:fim-expediente')->dailyAt('22:00');

/**
 * Fim do turno ADM (~18h)
 * Disparo extra para cobrir o turno administrativo.
 */
Schedule::command('notificar:fim-expediente')->dailyAt('18:00');

/**
 * Madrugada: resumo de ativos ainda fora do estoque no início do dia seguinte.
 * Útil para supervisores conferirem logo cedo.
 */
Schedule::command('notificar:fim-expediente')->dailyAt('06:00');