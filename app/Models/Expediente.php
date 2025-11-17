<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    use HasFactory;
    protected $table = 'expedientes';
    
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
    public function datosUnificados()
    {
        // Laravel buscará la tabla pivote 'dato_unificado_expediente' por defecto.
        // Como nuestra tabla se llama 'expediente_datos', debemos especificarlo.
        // También especificamos los nombres de las claves foráneas.
        return $this->belongsToMany(
            DatoUnificado::class, 
            'expediente_datos',      // Nombre de la tabla pivote
            'expediente_id',         // Clave foránea de este modelo en la tabla pivote
            'dato_unificado_id'      // Clave foránea del otro modelo en la tabla pivote
        );
    }
}