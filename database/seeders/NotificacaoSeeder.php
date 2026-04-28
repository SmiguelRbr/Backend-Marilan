<?php

namespace Database\Seeders;

use App\Models\Notificacao;
use Illuminate\Database\Seeder;

class NotificacaoSeeder extends Seeder
{
    public function run(): void
    {
        // Notificações para o Almoxarife (Geovane - user_id: 1)
        Notificacao::create([
            'user_id' => 1,
            'titulo' => 'Ferramenta em Atraso',
            'mensagem' => 'O colaborador PEDRO (1236) não devolveu a FURADEIRA BOSCH 2 após o fim do turno.',
            'tipo' => 'alerta',
            'lida' => false
        ]);

        Notificacao::create([
            'user_id' => 1,
            'titulo' => 'Manutenção Concluída',
            'mensagem' => 'A ferramenta LIXADEIRA BOSCH 8 está pronta para uso novamente.',
            'tipo' => 'sucesso',
            'lida' => true
        ]);

        // Notificações para o Colaborador (Pedro - user_id: 3)
        Notificacao::create([
            'user_id' => 3,
            'titulo' => 'Fim de Expediente Próximo',
            'mensagem' => 'Seu turno encerra em breve. Não esqueça de devolver as ferramentas em sua posse no Almoxarifado Central.',
            'tipo' => 'info',
            'lida' => false
        ]);

        Notificacao::create([
            'user_id' => 3,
            'titulo' => 'Transferência Recebida',
            'mensagem' => 'Você recebeu a posse do ARCO DE SERRA do colaborador BRUNO. A ferramenta já consta na sua lista.',
            'tipo' => 'info',
            'lida' => false
        ]);
    }
}