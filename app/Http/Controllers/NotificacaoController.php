<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use App\Models\Movimentacao;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificacaoController extends Controller
{
    /**
     * Lista todas as notificações do usuário logado,
     * mais recentes primeiro.
     */
    public function index(Request $request)
    {
        return $request->user()->notificacoes()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Marca uma única notificação como lida.
     * PATCH /notificacoes/{id}/ler
     */
    public function marcarComoLida($id)
    {
        $notificacao = Notificacao::findOrFail($id);
        $notificacao->update(['lida' => true]);

        return response()->json(['message' => 'Notificação marcada como lida']);
    }

    /**
     * Marca todas as notificações do usuário logado como lidas.
     * POST /notificacoes/ler-tudo
     */
    public function lerTudo(Request $request)
    {
        $request->user()->notificacoes()
            ->where('lida', false)
            ->update(['lida' => true]);

        return response()->json(['message' => 'Todas as notificações foram lidas']);
    }

    // ─── Helpers estáticos para disparar notificações ─────────────────────────

    /**
     * Cria uma notificação para um usuário específico.
     *
     * @param  int    $userId
     * @param  string $titulo
     * @param  string $mensagem
     * @param  string $tipo  'info' | 'alerta' | 'sucesso'
     */
    public static function criar(int $userId, string $titulo, string $mensagem, string $tipo = 'info'): void
    {
        Notificacao::create([
            'user_id'  => $userId,
            'titulo'   => $titulo,
            'mensagem' => $mensagem,
            'tipo'     => $tipo,
            'lida'     => false,
        ]);
    }

    /**
     * Dispara notificações de "Fim de expediente — ferramenta não devolvida".
     *
     * Deve ser chamado por um Artisan command agendado no final de cada turno.
     * Exemplo de agendamento em app/Console/Kernel.php:
     *
     *   $schedule->call(fn () => NotificacaoController::notificarFimExpediente())
     *            ->dailyAt('22:00');          // final do 2º turno
     *
     * O método percorre todas as movimentações abertas há mais de 8h,
     * notifica o colaborador E o(s) almoxarife(s).
     */
    public static function notificarFimExpediente(): void
    {
        $limite = Carbon::now()->subHours(8);

        $movimentacoesAtrasadas = Movimentacao::with(['ferramenta', 'usuario'])
            ->where('status', 'aberto')
            ->where('created_at', '<=', $limite)
            ->get();

        foreach ($movimentacoesAtrasadas as $mov) {
            $colaborador  = $mov->usuario;
            $ferramenta   = $mov->ferramenta;
            $horas        = (int) Carbon::parse($mov->created_at)->diffInHours(Carbon::now());

            // 1. Notifica o colaborador
            self::criar(
                $colaborador->id,
                'Devolução Pendente — Fim do Expediente',
                "A ferramenta {$ferramenta->descricao} (#{$ferramenta->codigo_patrimonio}) está com você há {$horas}h. " .
                "Por favor, devolva no Almoxarifado antes de sair.",
                'alerta'
            );

            // 2. Notifica todos os almoxarifes
            User::where('role', 'almoxarife')->each(function (User $almox) use ($colaborador, $ferramenta, $horas) {
                self::criar(
                    $almox->id,
                    'Ferramenta Não Devolvida',
                    "O colaborador {$colaborador->nome} (Crachá: {$colaborador->cracha}) não devolveu " .
                    "{$ferramenta->descricao} (#{$ferramenta->codigo_patrimonio}) após {$horas}h.",
                    'alerta'
                );
            });
        }
    }

    /**
     * Disparado quando uma ferramenta entra em manutenção.
     * Chamado manualmente em: FerramentaController@marcarManutencao (se existir).
     * Ou pode ser adicionado no endpoint de atualização de status.
     */
    public static function notificarManutencao(int $ferramentaId): void
    {
        $ferramenta = \App\Models\Ferramenta::find($ferramentaId);
        if (!$ferramenta) return;

        User::where('role', 'almoxarife')->each(function (User $almox) use ($ferramenta) {
            self::criar(
                $almox->id,
                'Ferramenta em Manutenção',
                "A ferramenta {$ferramenta->descricao} (#{$ferramenta->codigo_patrimonio}) foi " .
                "marcada para manutenção e está indisponível para retirada.",
                'alerta'
            );
        });
    }

    /**
     * Disparado quando uma ferramenta sai da manutenção (volta ao estoque).
     */
    public static function notificarManutencaoConcluida(int $ferramentaId): void
    {
        $ferramenta = \App\Models\Ferramenta::find($ferramentaId);
        if (!$ferramenta) return;

        User::where('role', 'almoxarife')->each(function (User $almox) use ($ferramenta) {
            self::criar(
                $almox->id,
                'Manutenção Concluída',
                "A ferramenta {$ferramenta->descricao} (#{$ferramenta->codigo_patrimonio}) " .
                "retornou ao estoque e já está disponível para retirada.",
                'sucesso'
            );
        });
    }

    /**
     * Disparado na devolução de uma ferramenta.
     * Chame em MovimentacaoController@devolver após o update.
     *
     * @param  \App\Models\Movimentacao $mov  A movimentação recém-concluída
     */
    public static function notificarDevolucao(\App\Models\Movimentacao $mov): void
    {
        $mov->loadMissing(['ferramenta', 'usuario']);
        $colaborador = $mov->usuario;
        $ferramenta  = $mov->ferramenta;

        // Avisa o almoxarife que recebeu a ferramenta de volta
        if ($mov->almoxarife_id) {
            self::criar(
                $mov->almoxarife_id,
                'Ferramenta Devolvida',
                "{$colaborador->nome} devolveu {$ferramenta->descricao} (#{$ferramenta->codigo_patrimonio}). " .
                "Item disponível no estoque.",
                'sucesso'
            );
        }
    }

    /**
     * Disparado quando uma transferência direta (troca) ocorre.
     * Chame em MovimentacaoController@trocar após criar a nova movimentação.
     *
     * @param  \App\Models\User        $novoColaborador
     * @param  \App\Models\Ferramenta  $ferramenta
     * @param  \App\Models\User|null   $anteriorColaborador
     */
    public static function notificarTroca(
        \App\Models\User $novoColaborador,
        \App\Models\Ferramenta $ferramenta,
        ?\App\Models\User $anteriorColaborador = null
    ): void {
        // Notifica o novo responsável
        self::criar(
            $novoColaborador->id,
            'Transferência Recebida',
            "Você recebeu a ferramenta {$ferramenta->descricao} (#{$ferramenta->codigo_patrimonio})" .
            ($anteriorColaborador ? " de {$anteriorColaborador->nome}" : '') .
            ". O item já consta na sua lista de custódia.",
            'info'
        );

        // Notifica o colaborador anterior que não é mais responsável
        if ($anteriorColaborador) {
            self::criar(
                $anteriorColaborador->id,
                'Ferramenta Transferida',
                "A ferramenta {$ferramenta->descricao} (#{$ferramenta->codigo_patrimonio}) " .
                "foi transferida para {$novoColaborador->nome}. Ela saiu da sua custódia.",
                'info'
            );
        }
    }

    /**
     * Disparado na retirada de ferramentas pelo almoxarife.
     * Chame em MovimentacaoController@retirar após criar as movimentações.
     *
     * @param  \App\Models\User  $colaborador
     * @param  array             $ferramentasRetiradas  [ ['descricao' => ..., 'codigo_patrimonio' => ...] ]
     */
    public static function notificarRetirada(\App\Models\User $colaborador, array $ferramentasRetiradas): void
    {
        $lista = collect($ferramentasRetiradas)
            ->map(fn ($f) => "{$f['descricao']} (#{$f['codigo_patrimonio']})")
            ->implode(', ');

        self::criar(
            $colaborador->id,
            'Ferramentas Retiradas',
            "Você retirou: {$lista}. Lembre-se de devolvê-las ao final do seu turno.",
            'info'
        );
    }
}