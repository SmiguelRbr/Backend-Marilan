<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    use HasFactory;

    // Nome exato da tabela no banco
    protected $table = 'notificacoes';

    protected $fillable = [
        'user_id',
        'titulo',
        'mensagem',
        'tipo',
        'lida'
    ];

    // Relacionamento: Uma notificação pertence a um usuário
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}