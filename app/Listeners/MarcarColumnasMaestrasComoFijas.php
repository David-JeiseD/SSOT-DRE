<?php

namespace App\Listeners;

use App\Events\IngestaCompletada;
use App\Models\ColumnaMaestra;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MarcarColumnasMaestrasComoFijas
{
    public function __construct()
    {
        //
    }

    public function handle(IngestaCompletada $event): void
    {
        // 🔥 Define aquí los nombres normalizados de las columnas que SIEMPRE deben ser fijas
        $nombresFijos = [
            'meses',
            'ano',
            'total_remuneracion',
            'total_descuento',
            'neto_a_pagar',
            'reint_',
            'observacion'
        ];

        // Busca todas las columnas maestras que coincidan con esos nombres
        // y actualiza su campo 'es_fijo' a 'true' (o 1).
        // Esto se hace en una única y eficiente consulta a la base de datos.
        ColumnaMaestra::whereIn('nombre_normalizado', $nombresFijos)
                      ->update(['es_fijo' => true]);
    }
}