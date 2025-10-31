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

    private function processDataRows(array $allRows, int $headerRowIndex, User $usuario, array $columnMap): array
    {
        $createdCount = 0;
        $updatedCount = 0;
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
            if (count(array_filter($row)) < 1) continue;
    
            $yearValue = trim(strval($row['A'] ?? ''));
            $monthRaw = trim(strval($row['B'] ?? ''));
            $monthValue = strtolower(substr(Str::ascii($monthRaw), 0, 3));
    
            if (!empty($yearValue) && is_numeric($yearValue)) $currentYear = $yearValue;
            if (empty($currentYear) || !isset($mesesMap[$monthValue])) continue;
            
            $fechaRegistro = Carbon::createFromDate($currentYear, $mesesMap[$monthValue], 1)->toDateString();
            
            $fullRowText = strtoupper(trim(implode(' ', array_filter($row))));
            $frasesClave = ['PLANILLA DETERIORADA', 'NO FIGURA', 'NO ENCONTRADO', 'SIN REGISTRO', 'DOCUMENTO INCOMPLETO', 'ERROR EN DATOS', 'PLANTILLA NO ENCONTRADA', 'NO EXISTE PLANILLA EN SEDE', 'DOCUMENTO DAÑADO', 'ARCHIVO CORRUPTO', 'PLANILLA ANULADO', 'NO FIGURA NOMBRE EN LA PLANILLA'];
            $esObservacion = false;
            $fraseEncontrada = null;
    
            foreach ($frasesClave as $frase) {
                if (str_contains($fullRowText, $frase)) {
                    $esObservacion = true;
                    $fraseEncontrada = $frase;
                    break;
                }
            }
    
            if ($esObservacion) {
                $idFilaOrigenObservacion = Str::uuid()->toString();
                $datoUnificado = DatoUnificado::firstOrCreate(
                    ['user_id' => $usuario->id, 'columna_maestra_id' => $observacionColumna->id, 'fecha_registro' => $fechaRegistro, 'valor' => $fraseEncontrada],
                    ['id_fila_origen' => $idFilaOrigenObservacion] 
                );
                if ($datoUnificado->wasRecentlyCreated) $createdCount++;
                else $skippedCount++;
            
            } else {
                $firstCell = trim(strval(reset($row)));
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
                    
                    if (!$this->isRowSignificant($datosFilaUnificados, $keyColumnIds)) {
                        $skippedCount++;
                    } else {
                        $idFilaOrigen = Str::uuid()->toString();
                        
                        // --- 🔥 INICIO DE LA LÓGICA FINAL Y CORRECTA ---
                        foreach ($datosFilaUnificados as $columnaMaestraId => $valor) {
                            // La clave de unicidad es la combinación de usuario, columna y fecha.
                            // El sistema buscará esta combinación. Si la encuentra, la actualiza. Si no, la crea.
                            $datoUnificado = DatoUnificado::updateOrCreate(
                                [
                                    'user_id' => $usuario->id,
                                    'columna_maestra_id' => $columnaMaestraId,
                                    'fecha_registro' => $fechaRegistro,
                                ],
                                [
                                    'valor' => $valor,
                                    'id_fila_origen' => $idFilaOrigen, // El id de fila se actualiza para saber de dónde vino el último dato
                                ]
                            );
    
                            if ($datoUnificado->wasRecentlyCreated) $createdCount++;
                            elseif ($datoUnificado->wasChanged()) $updatedCount++;
                            else $skippedCount++;
                        }
                        // --- 🔥 FIN DE LA LÓGICA FINAL Y CORRECTA ---
                    }
                } else {
                    $skippedCount++;
                }
            }
        }
    
        return [
            'creados' => $createdCount,
            'actualizados' => $updatedCount,
            'omitidos' => $skippedCount,
        ];
    }


    private function createAuditRecord(UploadedFile $file, User $subidoPor, array $resultado): void
    {
        $ruta = $file->store('archivos_historicos', 'public');
        ArchivoSubido::create([
            'subido_por_user_id' => $subidoPor->id,
            'nombre_original' => $file->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'filas_procesadas' => $resultado['creados'] + $resultado['actualizados'],
            'filas_omitidas' => $resultado['omitidos'],
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