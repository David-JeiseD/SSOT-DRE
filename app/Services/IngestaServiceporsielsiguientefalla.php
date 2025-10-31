<?php

namespace App\Services;

use App\Models\ArchivoSubido;
use App\Models\ColumnaMaestra;
use App\Models\DatoUnificado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IngestaService
{
     public function procesarArchivo(UploadedFile $file, User $usuarioDeDatos, User $subidoPor): array
    {
        return DB::transaction(function () use ($file, $usuarioDeDatos, $subidoPor) {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $headerRowIndex = $this->findHeaderRow($rows);
            if ($headerRowIndex === null) {
                throw new \Exception("No se pudo encontrar una fila de encabezado válida.");
            }
            
            $headerRow = $rows[$headerRowIndex];
            $columnMap = $this->processAndMapColumns($headerRow);
            
            // --- INICIO DE LA CORRECCIÓN CLAVE ---
            // Pasamos TODAS las filas y el índice del encabezado.
            // La lógica para saltarse las filas anteriores ahora está dentro de processDataRows.
            $resultado = $this->processDataRows($rows, $headerRowIndex, $usuarioDeDatos, $columnMap);
            // --- FIN DE LA CORRECCIÓN CLAVE ---

            $this->createAuditRecord($file, $subidoPor, $resultado);

            return [
                'procesados' => $resultado['processed_count'],
                'omitidos' => $resultado['skipped_count'],
            ];
        });
    }

    private function findHeaderRow(array $rows): ?int
    {
        $claves = ['año', 'mes', 'remun', 'desc', 'liquido'];
        foreach ($rows as $index => $row) {
            if (!array_filter($row)) continue;
            $coincidencias = 0;
            $rowString = strtolower(implode(' ', $row));
            foreach ($claves as $clave) {
                if (str_contains($rowString, $clave)) {
                    $coincidencias++;
                }
            }
            if ($coincidencias >= 3) {
                return $index;
            }
        }
        return null;
    }

    private function normalizeColumnName(string $name): string
    {
        if (empty(trim($name))) {
            return '';
        }
    
        $name = strtolower(Str::ascii(trim($name)));
    
        // --- 🔥 LÓGICA DE ALIAS ---
        // Clave: El nombre normalizado canónico que queremos.
        // Valor: Un array de posibles variaciones que la gente podría escribir.
        $aliases = [
            'total_remuneracion' => ['t.remun', 't remun', 'total rem', 'total remuneracion'],
            'total_descuento'    => ['t.desc', 't desc', 'total desc', 'total descuento'],
            'neto_a_pagar'       => ['liquido', 'líquido', 't.liquido', 'neto'],
            'ley_19990'          => ['ley 19990', 'b,ley 19990'],
            'ley_20530'          => ['ley 20530', 'a,ley 20530'],
            'afp'                => ['c,afp'],
            // ... puedes añadir todas las variaciones que se te ocurran aquí
        ];
    
        foreach ($aliases as $canonical => $variations) {
            // Pre-limpiamos las variaciones para comparar
            $cleanedVariations = array_map(function($v) {
                return preg_replace('/[^a-z0-9]+/', '', $v);
            }, $variations);
    
            // Limpiamos el nombre de entrada de la misma manera
            $cleanedInput = preg_replace('/[^a-z0-9]+/', '', $name);
    
            if (in_array($cleanedInput, $cleanedVariations)) {
                return $canonical; // ¡Encontrado! Devolvemos el nombre canónico.
            }
        }
        // --- 🔥 FIN DE LA LÓGICA DE ALIAS ---
    
        // Si no se encontró en los alias, usamos la normalización genérica
        return preg_replace('/[^a-z0-9]+/', '_', $name);
    }

    private function processAndMapColumns(array $headerRow): array
    {
        $map = [];
        $ignoredColumns = ['n', 'no'];
        $processedNames = [];
        foreach ($headerRow as $columnLetter => $columnName) {
            if (empty(trim($columnName))) continue;
            
            $normalizedName = $this->normalizeColumnName($columnName);
            if (in_array($normalizedName, $ignoredColumns) || in_array($normalizedName, $processedNames)) {
                continue;
            }
            
            $processedNames[] = $normalizedName;

            $columnaMaestra = ColumnaMaestra::firstOrCreate(
                ['nombre_normalizado' => $normalizedName],
                ['nombre_display' => trim($columnName)]
            );
            $map[$columnLetter] = $columnaMaestra->id;
        }
        return $map;
    }

    private function processDataRows(array $allRows, int $headerRowIndex, User $usuario, array $columnMap): array
{
    $processedCount = 0;
    $skippedCount = 0;
    $mesesMap = [
        'ene' => 1, 'enero' => 1, 'feb' => 2, 'febrero' => 2, 'mar' => 3, 'marzo' => 3,
        'abr' => 4, 'abril' => 4, 'may' => 5, 'mayo' => 5, 'jun' => 6, 'junio' => 6,
        'jul' => 7, 'julio' => 7, 'ago' => 8, 'agosto' => 8, 'sep' => 9, 'septiembre' => 9, 'set' => 9,
        'oct' => 10, 'octubre' => 10, 'nov' => 11, 'noviembre' => 11, 'dic' => 12, 'diciembre' => 12
    ];
    
    $currentYear = null;

    $observacionColumna = ColumnaMaestra::firstOrCreate(
        ['nombre_normalizado' => 'observacion'],
        ['nombre_display' => 'Observacion']
    );

    $keyColumnNames = [
        $this->normalizeColumnName('T.REMUN'),
        $this->normalizeColumnName('T.DESC'),
        $this->normalizeColumnName('LIQUIDO')
    ];
    $keyColumnIds = ColumnaMaestra::whereIn('nombre_normalizado', $keyColumnNames)
        ->pluck('id')->toArray();

    foreach ($allRows as $rowIndex => $row) {
        if ($rowIndex <= $headerRowIndex) continue;
        if (count(array_filter($row)) < 1) continue; // Solo ignorar si está 100% vacía

        $yearValue = trim(strval($row['A'] ?? ''));
        $monthRaw = trim(strval($row['B'] ?? ''));
        $monthValue = strtolower(substr(Str::ascii($monthRaw), 0, 3));

        if (!empty($yearValue) && is_numeric($yearValue)) $currentYear = $yearValue;
        if (empty($currentYear) || !isset($mesesMap[$monthValue])) continue;
        
        $fechaRegistro = Carbon::createFromDate($currentYear, $mesesMap[$monthValue], 1)->toDateString();

        // --- INICIO DE LA LÓGICA REORDENADA ---

        // 1. PRIMERO, buscamos si la fila es una observación de texto.
        $fullRowText = strtoupper(trim(implode(' ', array_filter($row))));
        $frasesClave = ['PLANILLA DETERIORADA', 'NO FIGURA', 'NO ENCONTRADO', 'SIN REGISTRO', 'DOCUMENTO INCOMPLETO', 'ERROR EN DATOS', 'PLANTILLA NO ENCONTRADA', 'NO EXISTE PLANILLA EN SEDE', 'DOCUMENTO DAÑADO', 'ARCHIVO CORRUPTO', 'PLANILLA ANULADO', 'NO FIGURA NOMBRE EN LA PLANILLA'];
        $esObservacion = false;

        foreach ($frasesClave as $frase) {
            if (str_contains($fullRowText, $frase)) {
                $idFilaOrigenObservacion = md5($frase);
                $datoUnificado = DatoUnificado::firstOrCreate(
                    ['user_id' => $usuario->id, 'columna_maestra_id' => $observacionColumna->id, 'fecha_registro' => $fechaRegistro, 'id_fila_origen' => $idFilaOrigenObservacion],
                    ['valor' => $frase] 
                );
                if ($datoUnificado->wasRecentlyCreated) $processedCount++;
                $esObservacion = true;
                break; 
            }
        }

        // 2. SI NO FUE UNA OBSERVACIÓN, entonces intentamos procesarla como fila de datos.
        if (!$esObservacion) {
            $firstCell = trim(strval(reset($row)));

            // Verificamos si parece una fila de datos (empieza con número)
            if (!empty($firstCell) && is_numeric($firstCell)) {
                $datosFilaUnificados = [];
                $columnasYaUsadas = [];
                foreach($row as $colLetter => $value) {
                    if (!isset($columnMap[$colLetter])) continue;
                    $colId = $columnMap[$colLetter];
                    $valLimpio = trim(strval($value));
                    if ($valLimpio !== '' && !in_array($colId, $columnasYaUsadas)) {
                        $datosFilaUnificados[$colId] = $valLimpio;
                        $columnasYaUsadas[] = $colId;
                    }
                }
                
                // Aplicamos el filtro financiero aquí
                if (!$this->isRowSignificant($datosFilaUnificados, $keyColumnIds)) {
                    $skippedCount++;
                } else {
                    ksort($datosFilaUnificados);
                    $idFilaOrigen = md5(implode('|', $datosFilaUnificados));
                    
                    $filaTuvoNuevosDatos = false;
                    foreach ($datosFilaUnificados as $columnaMaestraId => $valor) {
                        $datoUnificado = DatoUnificado::firstOrCreate(
                            ['user_id' => $usuario->id, 'columna_maestra_id' => $columnaMaestraId, 'fecha_registro' => $fechaRegistro, 'id_fila_origen' => $idFilaOrigen],
                            ['valor' => $valor]
                        );
                        if ($datoUnificado->wasRecentlyCreated) $filaTuvoNuevosDatos = true;
                    }
                    
                    if ($filaTuvoNuevosDatos) $processedCount++;
                    else $skippedCount++;
                }
            } else {
                // Si no fue observación Y no parece fila de datos, ahora sí la contamos como omitida.
                $skippedCount++;
            }
        }
        // --- FIN DE LA LÓGICA REORDENADA ---
    }

    return [
        'processed_count' => $processedCount,
        'skipped_count' => $skippedCount,
    ];
}

    private function createAuditRecord(UploadedFile $file, User $subidoPor, array $resultado): void
    {
        $ruta = $file->store('archivos_historicos', 'public');
        ArchivoSubido::create([
            'subido_por_user_id' => $subidoPor->id,
            'nombre_original' => $file->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'filas_procesadas' => $resultado['processed_count'],
            'filas_omitidas' => $resultado['skipped_count'],
        ]);
    }
    private function isRowSignificant(array $unifiedRowData, array $keyColumnIds): bool
    {
        // Si no hemos definido columnas clave, por seguridad, consideramos todas las filas significativas.
        if (empty($keyColumnIds)) {
            return true;
        }

        foreach ($keyColumnIds as $keyId) {
            // Verificamos si la fila contiene un dato para una de las columnas clave
            if (isset($unifiedRowData[$keyId])) {
                $valor = $unifiedRowData[$keyId];
                // Verificamos si ese valor es numérico y mayor que cero
                if (is_numeric($valor) && (float)$valor > 0) {
                    return true; // Encontramos un valor significativo, la fila es válida.
                }
            }
        }

        // Si recorrimos todas las columnas clave y no encontramos ningún valor > 0, la fila no es significativa.
        return false;
    }
}