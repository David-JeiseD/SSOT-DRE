<?php
// app/Http/Controllers/Admin/PlantillaController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ColumnaMaestra;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PlantillaController extends Controller
{
    public function generarPersonalizada(Request $request)
    {
        // 1. Validar la entrada (sin cambios)
        $validated = $request->validate([
            'columnas' => 'required|array|min:1',
            'columnas.*' => 'exists:columnas_maestras,id',
        ]);

        // 2. Obtener y ordenar las columnas seleccionadas (sin cambios)
        $columnas = ColumnaMaestra::find($validated['columnas']);
        
        $ordenInicio = ['meses', 'ano', 'total_remuneracion', 'total_descuento'];
        $ordenFinal = ['reint_', 'neto_a_pagar'];
        
        $columnasOrdenadas = $columnas->sortBy(function ($columna) use ($ordenInicio, $ordenFinal) {
            $nombre = $columna->nombre_normalizado;
            $posicionInicio = array_search($nombre, $ordenInicio);
            if ($posicionInicio !== false) return $posicionInicio;
            $posicionFinal = array_search($nombre, $ordenFinal);
            if ($posicionFinal !== false) return 1000 + $posicionFinal;
            return 500;
        });

        // 3. Generar el archivo Excel con PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 🔥 ========================================================== 🔥
        // 🔥 CAMBIO PRINCIPAL: Añadimos la columna 'N°' al principio 🔥
        // ========================================================== 🔥

        // Escribimos la primera cabecera 'N°' en la celda A1
        $sheet->setCellValue('A1', 'N°');
        
        // Empezamos a escribir el resto de las cabeceras desde la columna B
        $columnaLetra = 'B'; 
        foreach ($columnasOrdenadas as $columna) {
            $sheet->setCellValue($columnaLetra . '1', $columna->nombre_display);
            
            // Avanzamos a la siguiente letra del abecedario
            $columnaLetra++; 
        }

        // 4. Forzar la descarga (sin cambios)
        $nombreArchivo = "Plantilla_Personalizada_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}