<?php

namespace App\Services;

use App\Models\ArchivoSubido;
use App\Models\AccionUsuario;
use App\Models\ColumnaMaestra;
use App\Models\Constancia;
use App\Models\DatoUnificado;
use App\Models\Expediente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IngestaService
{
      /**
     * Orquesta el proceso completo de leer, limpiar, unificar y almacenar
     * los datos de un archivo Excel subido.
     *
     * @param UploadedFile $file El archivo subido desde el formulario.
     * @param User $usuarioDeDatos El usuario al que pertenecen los datos del archivo.
     * @param User $subidoPor El usuario que está realizando la acción de subida.
     * @param array $formData Los datos del formulario (números de constancia/expediente, etc.).
     * @return array Un resumen del resultado (filas procesadas y omitidas).
     * @throws \Exception Si ocurre un error, para que el controlador lo capture.
     */
    public function procesarArchivo(UploadedFile $file, User $usuarioDeDatos, User $subidoPor, array $formData): array
    {
        return DB::transaction(function () use ($file, $usuarioDeDatos, $subidoPor, $formData) {
            
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            // 1. 🔥 MEJORA: Pre-filtrar filas basura (nombre del usuario, paginación, etc.)
            $rows = $this->preFilterRows($rows);

            $headerRowIndex = $this->findHeaderRow($rows);
            if ($headerRowIndex === null) {
                throw new \Exception("No se pudo encontrar una fila de encabezado válida en el archivo Excel. Asegúrese de que contenga columnas como 'AÑO' y 'MES'.");
            }
            $headerRow = $rows[$headerRowIndex];

            $columnMap = $this->processAndMapColumns($headerRow);

            // Empezamos a leer los datos desde la fila SIGUIENTE al encabezado
            $dataRows = array_slice($rows, $headerRowIndex + 1);
            $resultadoProcesamiento = $this->processDataRows($dataRows, $usuarioDeDatos, $columnMap);

            $expediente = $this->createDocumentRecords($usuarioDeDatos, $subidoPor, $formData);
            
            // Solo adjuntar si se procesaron datos
            if (!empty($resultadoProcesamiento['processed_ids'])) {
                $expediente->datos()->attach($resultadoProcesamiento['processed_ids']);
            }

            $this->createAuditRecords($file, $subidoPor, $expediente, $resultadoProcesamiento);

            return [
                'procesadas' => $resultadoProcesamiento['processed_count'],
                'omitidas' => $resultadoProcesamiento['skipped_count'],
            ];
        });
    }

     /**
     * 🔥 NUEVO: Elimina filas que no contienen datos tabulares (ej. separadores de página).
     */
    private function preFilterRows(array $rows): array
    {
        return array_filter($rows, function($row) {
            // Si la fila está completamente vacía, la eliminamos.
            if (!is_array($row) || !array_filter($row)) {
                return false;
            }
            // Si la fila tiene menos de 3 celdas con datos, probablemente es basura (ej. "JESUS SANTOS" o "PAG.07").
            if (count(array_filter($row)) < 3) {
                return false;
            }
            // Si es una fila de encabezado repetida, la eliminamos.
            $rowString = strtoupper(implode(' ', $row));
            if (str_contains($rowString, 'T.REMUN') && str_contains($rowString, 'LIQUIDO')) {
                 // Dejamos que findHeaderRow la encuentre, pero las copias se filtrarán aquí si tienen menos de 3 celdas
                 // o si se detectan por otra regla. Esta es una salvaguarda.
            }
            return true;
        });
    }

    /**
     * 🔥 RESCATADO: Lógica mejorada para encontrar la fila del encabezado.
     * Busca la primera fila que contenga al menos 3 de las palabras clave.
     */
    private function findHeaderRow(array $rows): ?int
    {
        // Usamos las claves ya normalizadas
        $claves = ['ano', 'meses', 't_remun', 't_desc', 'liquido']; 
        
        foreach ($rows as $index => $row) {
            if (!is_array($row) || !array_filter($row)) continue;

            $coincidencias = 0;
            foreach ($row as $celda) {
                // Normalizamos la celda de la misma manera para comparar
                $celdaNormalizada = $this->normalizeColumnName(strval($celda));
                foreach ($claves as $clave) {
                    if (str_contains($celdaNormalizada, $clave)) {
                        $coincidencias++;
                        break;
                    }
                }
            }
            if ($coincidencias >= 3) {
                return $index;
            }
        }
        return null;
    }

    /**
     * 🔥 RESCATADO: Normalización mejorada.
     * Convierte a minúsculas, reemplaza caracteres especiales y espacios con '_'.
     * También maneja alias comunes.
     */
    private function normalizeColumnName(?string $name): string
    {
        if ($name === null || trim($name) === '') {
            return '';
        }
        
        // 1. Convertir a minúsculas y quitar acentos
        $cleanedName = strtolower(Str::ascii(trim($name)));
        
        // 2. Reemplazar cualquier secuencia de caracteres no alfanuméricos con un solo guion bajo
        $cleanedName = preg_replace('/[^a-z0-9]+/', '_', $cleanedName);
        
        // 3. Quitar guiones bajos al principio o al final
        return trim($cleanedName, '_');
    }
    /**
     * Procesa la fila de encabezado, crea registros en `columnas_maestras` si no existen,
     * y devuelve un mapa de [LetraDeColumna => ID_ColumnaMaestra].
     */
    private function processAndMapColumns(array $headerRow): array
    {
        $map = [];
        $ignoredNormalizedNames = ['n', 'no']; // La normalización de 'Nº' dará 'n' o 'no'
        $processedNormalizedNames = [];

        foreach ($headerRow as $columnLetter => $columnName) {
            
            $nombreDisplay = trim(strval($columnName));
            if ($nombreDisplay === '') {
                continue;
            }

            $nombreNormalizado = $this->normalizeColumnName($nombreDisplay);

            if (
                $nombreNormalizado === '' ||
                in_array($nombreNormalizado, $ignoredNormalizedNames) ||
                in_array($nombreNormalizado, $processedNormalizedNames)
            ) {
                continue;
            }

            $processedNormalizedNames[] = $nombreNormalizado;

            // --- ¡EL DD() CLAVE VA AQUÍ! ---
            // Vamos a ver qué estamos a punto de buscar en la BD
            $datosParaBuscar = ['nombre_normalizado' => $nombreNormalizado];
            //dd('A punto de ejecutar firstOrCreate con:', $datosParaBuscar);

            $columnaMaestra = ColumnaMaestra::firstOrCreate(
                $datosParaBuscar,
                ['nombre_display' => $nombreDisplay]
            );
            
            $map[$columnLetter] = $columnaMaestra->id;
        }
        
        return $map;
    }
    /**
     * Itera sobre las filas de datos, las limpia y las inserta en `datos_unificados`.
     */
        private function processDataRows(array $dataRows, User $usuario, array $columnMap): array
    {
        $processedIds = [];
        $processedCount = 0;
        $skippedCount = 0;
        // Mapa de meses más robusto
        $mesesMap = [
            'ene' => 1, 'enero' => 1, 'feb' => 2, 'febrero' => 2, 'mar' => 3, 'marzo' => 3,
            'abr' => 4, 'abril' => 4, 'may' => 5, 'mayo' => 5, 'jun' => 6, 'junio' => 6,
            'jul' => 7, 'julio' => 7, 'ago' => 8, 'agosto' => 8, 'sep' => 9, 'septiembre' => 9,
            'oct' => 10, 'octubre' => 10, 'nov' => 11, 'noviembre' => 11, 'dic' => 12, 'diciembre' => 12
        ];
        
        $currentYear = null;

        foreach ($dataRows as $row) {
            // La pre-filtración ya eliminó la mayoría de las filas basura.
            // Ahora, solo verificamos que la fila tenga datos numéricos para ser considerada.
            $firstCell = reset($row);
            if (!is_numeric(trim(strval($firstCell)))) {
                continue;
            }
            
            // Asumimos que la columna 'A' es el año y 'B' es el mes.
            // Esta lógica se podría hacer más robusta si las columnas A y B no siempre son año/mes.
            $year = trim(strval($row['A'] ?? ''));
            $monthName = substr(strtolower(Str::ascii(trim(strval($row['B'] ?? '')))), 0, 3); // ej: "enero." -> "ene"

            if (!empty($year) && is_numeric($year)) {
                $currentYear = $year;
            }
            
            if (empty($currentYear) || !isset($mesesMap[$monthName])) {
                continue; // Si no podemos determinar una fecha válida, saltamos la fila.
            }

            $fechaRegistro = Carbon::createFromDate($currentYear, $mesesMap[$monthName], 1);

            foreach ($row as $columnLetter => $valor) {
                if (!isset($columnMap[$columnLetter]) || is_null($valor) || trim(strval($valor)) === '') {
                    continue;
                }
                
                $valorLimpio = trim(strval($valor));

                $datoUnificado = DatoUnificado::updateOrCreate(
                    [
                        'user_id' => $usuario->id,
                        'columna_maestra_id' => $columnMap[$columnLetter],
                        'fecha_registro' => $fechaRegistro->toDateString(),
                    ],
                    ['valor' => $valorLimpio]
                );

                if ($datoUnificado->wasRecentlyCreated) $processedCount++;
                elseif (!$datoUnificado->wasChanged()) $skippedCount++;
                
                $processedIds[] = $datoUnificado->id;
            }
        }

        return [
            'processed_ids' => array_unique($processedIds),
            'processed_count' => $processedCount,
            'skipped_count' => $skippedCount,
        ];
    }
    
        /**
     * Crea los registros de Constancia y Expediente.
     */
    private function createDocumentRecords(User $usuario, User $subidoPor, array $formData): Expediente
    {
        $constancia = Constancia::create([
            'user_id' => $usuario->id,
            'tipo_documento_id' => $formData['tipo_documento_id'],
            'numero_constancia' => $formData['numero_constancia'],
        ]);

        return Expediente::create([
            'constancia_id' => $constancia->id,
            'numero_expediente' => $formData['numero_expediente'],
            'generado_por_user_id' => $subidoPor->id,
        ]);
    }

    /**
     * Crea los registros de auditoría.
     */
    private function createAuditRecords(UploadedFile $file, User $subidoPor, Expediente $expediente, array $resultado): void
    {
        // 1. Guardar el archivo físico y registrarlo
        $ruta = $file->store('archivos_historicos', 'public');
        ArchivoSubido::create([
            'subido_por_user_id' => $subidoPor->id,
            'nombre_original' => $file->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'filas_procesadas' => $resultado['processed_count'],
            'filas_omitidas' => $resultado['skipped_count'],
        ]);

        // 2. Registrar la acción del usuario
        AccionUsuario::create([
            'user_id' => $subidoPor->id,
            'tipo_accion' => 'ingesta',
            'referencia_id' => $expediente->id,
            'referencia_tipo' => Expediente::class,
        ]);
    }
}