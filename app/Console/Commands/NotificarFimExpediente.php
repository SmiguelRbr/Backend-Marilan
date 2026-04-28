<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificacaoController;
use Illuminate\Console\Command;

class NotificarFimExpediente extends Command
{
    /**
     * php artisan notificar:fim-expediente
     *
     * Percorre movimentações abertas há mais de 8h e envia alertas
     * para colaboradores e almoxarifes sobre ferramentas não devolvidas.
     */
    protected $signature   = 'notificar:fim-expediente';
    protected $description = 'Envia notificações de ferramentas não devolvidas ao fim do expediente';

    public function handle(): int
    {
        $this->info('Verificando movimentações em atraso…');

        NotificacaoController::notificarFimExpediente();

        $this->info('Notificações de fim de expediente enviadas com sucesso.');
        return Command::SUCCESS;
    }
}