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
            
            $resultado = $this->processDataRows($rows, $headerRowIndex, $usuarioDeDatos, $columnMap);

            // Pasamos el resultado completo al método de auditoría
            $this->createAuditRecord($file, $subidoPor, $resultado);

            // Devolvemos el resultado completo al controlador
            return $resultado;
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




        /**
     * 🔥 REFACTORIZADO FINAL: El director de orquesta.
     */
    private function processDataRows(array $allRows, int $headerRowIndex, User $usuario, array $columnMap): array
    {
        $counters = ['filas_procesadas' => 0, 'filas_omitidas' => 0, 'registros_creados' => 0];
        $mesesMap = [
            'ene' => 1, 'enero' => 1, 'feb' => 2, 'febrero' => 2, 'mar' => 3, 'marzo' => 3,
            'abr' => 4, 'abril' => 4, 'may' => 5, 'mayo' => 5, 'jun' => 6, 'junio' => 6,
            'jul' => 7, 'julio' => 7, 'ago' => 8, 'agosto' => 8, 'sep' => 9, 'septiembre' => 9, 'set' => 9,
            'oct' => 10, 'octubre' => 10, 'nov' => 11, 'noviembre' => 11, 'dic' => 12, 'diciembre' => 12
        ];
        $keyColumnNames = [
            $this->normalizeColumnName('T.REMUN'),
            $this->normalizeColumnName('T.DESC'),
            $this->normalizeColumnName('LIQUIDO')
        ];
        $keyColumnIds = ColumnaMaestra::whereIn('nombre_normalizado', $keyColumnNames)
            ->pluck('id')->toArray();
        
        $currentYear = null;

        foreach ($allRows as $rowIndex => $row) {
            if ($rowIndex <= $headerRowIndex || count(array_filter($row)) < 1) continue;

            $yearValue = trim(strval($row['C'] ?? ''));
            if (!empty($yearValue) && is_numeric($yearValue)) $currentYear = $yearValue;
            
            $monthRaw = trim(strval($row['B'] ?? ''));
            
            $fullRowText = strtoupper(trim(implode(' ', array_filter($row))));
            $frasesClave = ['PLANILLA DETERIORADA', 'NO FIGURA', 'NO ENCONTRADO', 'SIN REGISTRO', 'DOCUMENTO INCOMPLETO', 'ERROR EN DATOS', 'PLANTILLA NO ENCONTRADA', 'NO EXISTE PLANILLA EN SEDE', 'DOCUMENTO DAÑADO', 'ARCHIVO CORRUPTO', 'PLANILLA ANULADO', 'NO FIGURA NOMBRE EN LA PLANILLA'];
            
            $esObservacion = false;
            foreach ($frasesClave as $frase) {
                if (str_contains($fullRowText, $frase)) {
                    $this->handleObservationRow($currentYear, $monthRaw, $frase, $mesesMap, $usuario, $counters);
                    $esObservacion = true;
                    break;
                }
            }
            
            if (!$esObservacion) {
                $this->handleNumericRow($row, $currentYear, $monthRaw, $mesesMap, $usuario, $columnMap, $keyColumnIds, $counters);
            }
        }
        
        return $counters;
    }

/**
 * 🔥 CORREGIDO: Usa Hashing DETERMINISTA para el id_fila_origen.
 */
    private function handleNumericRow($row, $currentYear, $monthRaw, $mesesMap, $usuario, $columnMap, $keyColumnIds, &$counters)
    {
        $monthValue = strtolower(substr(Str::ascii($monthRaw), 0, 3));
        if (empty($currentYear) || !isset($mesesMap[$monthValue])) { $counters['filas_omitidas']++; return; }
        $firstCell = trim(strval(reset($row)));
        if (empty($firstCell) || !is_numeric($firstCell)) { $counters['filas_omitidas']++; return; }

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

        if (!$this->isRowSignificant($datosFilaUnificados, $keyColumnIds)) { $counters['filas_omitidas']++; return; }
        

        // --- 🔥 NUEVA LÓGICA DE HASHING DETERMINISTA (CORREGIDA) ---
        // 1. Creamos un array solo con los datos de las columnas clave para el hash.
        $datosParaHash = [];
        foreach ($keyColumnIds as $keyId) {
            // Usamos el valor si existe, o una cadena vacía si no, para asegurar consistencia.
            $datosParaHash[$keyId] = $datosFilaUnificados[$keyId] ?? '';
        }

        // 2. Ordenamos por clave (ID de columna) para que el orden no afecte el resultado.
        ksort($datosParaHash);

        // 3. Generamos el hash SÓLO con los datos clave. Este hash será el mismo
        //    incluso si se añaden columnas nuevas como FONAVI en subidas posteriores.
        $idFilaOrigen = md5($currentYear . $monthRaw . implode('|', $datosParaHash));
        // --- 🔥 FIN DE LA NUEVA LÓGICA ---

        $fechaRegistro = Carbon::createFromDate($currentYear, $mesesMap[$monthValue], 1)->toDateString();
        $filaTuvoNuevosDatos = false;

        // El resto del bucle `foreach` para guardar los datos permanece EXACTAMENTE IGUAL.
        foreach ($datosFilaUnificados as $columnaMaestraId => $valor) {
            $datoUnificado = DatoUnificado::firstOrCreate(
                // Esta parte es la clave: busca usando el idFilaOrigen ahora estable.
                ['user_id' => $usuario->id, 'columna_maestra_id' => $columnaMaestraId, 'fecha_registro' => $fechaRegistro, 'id_fila_origen' => $idFilaOrigen],
                ['valor' => $valor]
            );
            if ($datoUnificado->wasRecentlyCreated) {
                $filaTuvoNuevosDatos = true;
                $counters['registros_creados']++;
            }
        }

        if ($filaTuvoNuevosDatos) $counters['filas_procesadas']++;
        else $counters['filas_omitidas']++;
    }


/**
 * 🔥 CORREGIDO: Usa Hashing DETERMINISTA para el id_fila_origen.
    */
    private function handleObservationRow($currentYear, $monthRaw, $fraseEncontrada, $mesesMap, $usuario, &$counters)
    {
        $monthValue = strtolower(substr(Str::ascii($monthRaw), 0, 3));
        if (empty($currentYear) || !isset($mesesMap[$monthValue])) { $counters['filas_omitidas']++; return; }

        // --- LÓGICA DE HASHING DETERMINISTA ---
        $idFilaOrigen = md5($currentYear . $monthRaw . $fraseEncontrada);
        // --- FIN LÓGICA DE HASHING ---
        
        $fechaRegistro = Carbon::createFromDate($currentYear, $mesesMap[$monthValue], 1)->toDateString();
        $anioColumna = ColumnaMaestra::firstOrCreate(['nombre_normalizado' => 'ano'], ['nombre_display' => 'AÑO']);
        $mesColumna = ColumnaMaestra::firstOrCreate(['nombre_normalizado' => 'meses'], ['nombre_display' => 'MESES']);
        $observacionColumna = ColumnaMaestra::firstOrCreate(['nombre_normalizado' => 'observacion'], ['nombre_display' => 'Observacion']);
        
        $filaTuvoNuevosDatos = false;
        $datosParaGuardar = [$anioColumna->id => $currentYear, $mesColumna->id => $monthRaw, $observacionColumna->id => $fraseEncontrada];

        foreach ($datosParaGuardar as $colId => $valor) {
            $datoUnificado = DatoUnificado::firstOrCreate(
                ['user_id' => $usuario->id, 'columna_maestra_id' => $colId, 'fecha_registro' => $fechaRegistro, 'id_fila_origen' => $idFilaOrigen],
                ['valor' => $valor]
            );
            if ($datoUnificado->wasRecentlyCreated) {
                $filaTuvoNuevosDatos = true;
                $counters['registros_creados']++;
            }
        }

        if ($filaTuvoNuevosDatos) $counters['filas_procesadas']++;
        else $counters['filas_omitidas']++;
    }

    

    private function createAuditRecord(UploadedFile $file, User $subidoPor, array $resultado): void
    {
        $ruta = $file->store('archivos_historicos', 'public');
        ArchivoSubido::create([
            'subido_por_user_id' => $subidoPor->id,
            'nombre_original' => $file->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'filas_procesadas' => $resultado['filas_procesadas'], // Clave actualizada
            'filas_omitidas' => $resultado['filas_omitidas'],   // Clave actualizada
        ]);
    }

    private function isRowSignificant(array $unifiedRowData, array $keyColumnIds): bool 
    {
        if (empty($keyColumnIds)) return true;
        foreach ($keyColumnIds as $keyId) {
            if (isset($unifiedRowData[$keyId])) {
                $valor = $unifiedRowData[$keyId];
                if (is_numeric($valor) && (float)$valor > 0) return true;
            }
        }
        return false;
    }
}