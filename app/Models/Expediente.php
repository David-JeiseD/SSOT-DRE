<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function constancia() {
        return $this->belongsTo(Constancia::class);
    }

    // Usamos un nombre diferente para no confundir con el usuario de la constancia
    public function generadoPor() {
        return $this->belongsTo(User::class, 'generado_por_user_id');
    }
    
    // RELACIÓN MUCHOS A MUCHOS: Un expediente es una colección de datos unificados
    public function datos() {
        return $this->belongsToMany(DatoUnificado::class, 'expediente_datos');
    }
}