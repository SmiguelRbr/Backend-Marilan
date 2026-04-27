<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ferramenta extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_patrimonio',
        'nome',
        'descricao',
        'status',
    ];

    public function movimentacoes()
    {
        return $this->hasMany(Movimentacao::class);
    }
}
