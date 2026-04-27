<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes';

    protected $fillable = [
        'ferramenta_id',
        'usuario_id',
        'almoxarife_id',
        'setor',
        'data_retirada',
        'data_devolucao',
        'status',
    ];

    public function ferramenta()
    {
        return $this->belongsTo(Ferramenta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function almoxarife()
    {
        return $this->belongsTo(User::class, 'almoxarife_id');
    }
}