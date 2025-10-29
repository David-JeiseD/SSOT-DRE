<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatoUnificado extends Model
{
    use HasFactory;
    
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
}