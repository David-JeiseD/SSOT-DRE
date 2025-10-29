<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivoSubido extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    public function subidoPor() {
        return $this->belongsTo(User::class, 'subido_por_user_id');
    }
}