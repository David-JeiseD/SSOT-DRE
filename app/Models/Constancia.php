<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Constancia extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function tipoDocumento() {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function expedientes() {
        return $this->hasMany(Expediente::class);
    }
}