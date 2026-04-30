<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrocaP2P extends Model
{
    use HasFactory;

    protected $table = 'trocas_p2p';

    protected $fillable = [
        'de_user_id',
        'para_user_id',
        'ferramentas',
        'status'
    ];

    protected $casts = [
        'ferramentas' => 'array',
    ];

    public function deUsuario()
    {
        return $this->belongsTo(User::class, 'de_user_id');
    }

    public function paraUsuario()
    {
        return $this->belongsTo(User::class, 'para_user_id');
    }
}