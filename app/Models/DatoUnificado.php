<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatoUnificado extends Model
{
    use HasFactory;
    protected $table = 'datos_unificados';
    protected $guarded = [];
    protected $casts = ['fecha_registro' => 'date'];

    // RELACIONES: A qué "PERTENECE" este dato
    
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function columnaMaestra() {
        return $this->belongsTo(ColumnaMaestra::class);
    }

    // RELACIÓN MUCHOS A MUCHOS: Un dato puede estar en múltiples expedientes
    public function expedientes() {
        return $this->belongsToMany(Expediente::class, 'expediente_datos');
    }
    public function filaOrigen()
    {
        // Una relación "hasMany" que devuelve todos los otros datos que comparten el mismo id_fila_origen.
        return $this->hasMany(DatoUnificado::class, 'id_fila_origen', 'id_fila_origen');
    }
}