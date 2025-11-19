<?php
// app/Http/Controllers/Traits/GeneratesConstanciaPdf.php

namespace App\Http\Controllers\Traits;

use App\Models\Expediente;
use App\Models\ColumnaMaestra;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

trait GeneratesConstanciaPdf
{
    /**
     * Función centralizada que toma los datos y genera la descarga del PDF.
     */
    public function streamConstanciaPdf(
        Expediente $expediente, 
        User $usuario, 
        User $generadoPor,
        Collection $datosCrudos
    ) {
        // 1. Obtenemos y ordenamos las columnas
        $columnasIds = $datosCrudos->pluck('columna_maestra_id')->unique();
        $columnas = ColumnaMaestra::find($columnasIds);
        $ordenInicio = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion'];
        $ordenFinal = ['ref_mov', 'reint_', 'neto_a_pagar'];
        $columnasOrdenadas = $columnas->sortBy(function ($columna) use ($ordenInicio, $ordenFinal) {
            $nombre = $columna->nombre_normalizado;
            $posicionInicio = array_search($nombre, $ordenInicio);
            if ($posicionInicio !== false) return $posicionInicio;
            $posicionFinal = array_search($nombre, $ordenFinal);
            if ($posicionFinal !== false) return 1000 + $posicionFinal;
            return 500;
        });

        // 2. Pivotamos la tabla
        $tabla = [];
        foreach ($datosCrudos as $dato) {
            $claveFila = $dato->id_fila_origen;
            if (!isset($tabla[$claveFila])) {
                $tabla[$claveFila] = ['fecha' => $dato->fecha_registro, 'datos' => []];
            }
            $tabla[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        uasort($tabla, fn($a, $b) => $a['fecha'] <=> $b['fecha']);

        // 3. Juntamos todos los datos que la vista del PDF necesita
        $data = [
            'expediente' => $expediente,
            'usuario' => $usuario,
            'generadoPor' => $generadoPor,
            'columnas' => $columnasOrdenadas,
            'tabla' => $tabla
        ];

        // 4. Generamos el PDF
        $pdf = Pdf::loadView('pdf.constancia', $data);

        // 5. Forzamos la descarga
        $nombreArchivo = "Constancia_{$expediente->numero_expediente}_{$usuario->dni}.pdf";
        return $pdf->download($nombreArchivo);
    }
}