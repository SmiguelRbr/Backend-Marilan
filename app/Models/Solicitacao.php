<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitacao extends Model
{
    use HasFactory;

    protected $table = 'solicitacoes';

    protected $fillable = [
        'colaborador_id',
        'almoxarife_id',
        'ferramentas',   // JSON
        'status',
        'motivo_recusa',
    ];

    protected $casts = [
        'ferramentas' => 'array',
    ];

    // ─── Relacionamentos ──────────────────────────────────────────────────────

    public function colaborador()
    {
        return $this->belongsTo(User::class, 'colaborador_id');
    }

    public function almoxarife()
    {
        return $this->belongsTo(User::class, 'almoxarife_id');
    }

    // ─── Escopos úteis ────────────────────────────────────────────────────────

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }
}