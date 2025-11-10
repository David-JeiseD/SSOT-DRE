<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccionUsuario extends Model
{
    use HasFactory;

    // Nombre de tabla personalizado si no sigue la convención de Laravel
    protected $table = 'acciones_usuario'; 
    
    protected $guarded = [];
    protected $casts = [
        'metadata' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    // RELACIÓN POLIMÓRFICA: La magia de 'referencia'
    public function referencia()
    {
        return $this->morphTo();
    }
}