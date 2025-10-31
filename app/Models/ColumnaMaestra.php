<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ColumnaMaestra extends Model
{
    use HasFactory;
    protected $table = 'columnas_maestras';

    protected $guarded = []; // Permite asignación masiva de todos los campos

    public function datos() {
        return $this->hasMany(DatoUnificado::class);
    }
}
