<?php

namespace App\Http\Controllers;

use App\Models\Constancia;
use App\Models\Expediente;
use App\Models\TipoDocumento;
use App\Models\User;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ExpedienteController extends Controller
{
    /**
     * Muestra la interfaz de búsqueda y los resultados de las constancias y sus expedientes.
     */
    public function index(Request $request)
    {
        // Obtenemos los datos para los filtros del formulario (usuarios y tipos de documento)
        $usuarios = User::orderBy('name')->get();
        $tiposDocumento = TipoDocumento::orderBy('nombre')->get();

        // Construimos la consulta base para las Constancias
        $query = Constancia::query()->with([
            'user', // Carga el usuario al que pertenece la constancia
            'tipoDocumento', // Carga el tipo de documento
            'expedientes.generadoPor' // Carga los expedientes y, para cada uno, el usuario que lo generó
        ]);

        // --- Aplicamos los filtros si existen en la petición ---
        if ($request->filled('numero_constancia')) {
            $query->where('numero_constancia', 'like', '%' . $request->numero_constancia . '%');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('tipo_documento_id')) {
            $query->where('tipo_documento_id', $request->tipo_documento_id);
        }

        // Ordenamos por la más reciente y paginamos los resultados
        $constancias = $query->latest()->paginate(15);

        // Devolvemos la vista con todas las variables necesarias
        return view('expedientes.index', [
            'constancias' => $constancias,
            'usuarios' => $usuarios,
            'tiposDocumento' => $tiposDocumento,
            'inputs' => $request->all(), // Para mantener los filtros seleccionados
        ]);
    }

    /**
     * Genera y descarga un archivo Excel para un expediente específico.
     */
    public function descargar(Expediente $expediente)
    {
        // 1. AUDITORÍA: Registrar quién está descargando el expediente (con la corrección).
        auth()->user()->acciones()->create([
            'tipo_accion' => 'DESCARGA_EXPEDIENTE',
            'referencia_id' => $expediente->id,
            'referencia_tipo' => Expediente::class,
        ]);

        // 2. OBTENER LOS DATOS CRUDOS ASOCIADOS AL EXPEDIENTE
        $datosCrudos = $expediente->datosUnificados()->with('columnaMaestra')->get();
        
        // 3. PIVOTAR LOS DATOS PARA CONVERTIRLOS EN FILAS DE EXCEL
        // Agrupamos todos los datos que pertenecen a la misma fila original usando 'id_fila_origen'
        $filasAgrupadas = [];
        foreach ($datosCrudos as $dato) {
            $idFila = $dato->id_fila_origen;
            $nombreColumna = $dato->columnaMaestra->nombre_display;
            $filasAgrupadas[$idFila][$nombreColumna] = $dato->valor;
        }

        // 4. OBTENER Y ORDENAR LOS ENCABEZADOS (HEADERS)
        $columnas = $datosCrudos->pluck('columnaMaestra')->unique('id');
        $ordenPersonalizado = ['MESES', 'AÑO', 'T.REMUN', 'T.DESC.', 'LIQUIDO', 'REINT.', 'Observacion'];
        
        $encabezadosOrdenados = $columnas->sortBy(function ($columna) use ($ordenPersonalizado) {
            $posicion = array_search($columna->nombre_display, $ordenPersonalizado);
            return $posicion === false ? 999 : $posicion; // Si no está en el orden, va al final
        })->pluck('nombre_display');

        // 5. GENERAR EL ARCHIVO EXCEL CON PHPSPREADSHEET
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Escribimos la fila de encabezados
        $columnaLetra = 'A';
        foreach ($encabezadosOrdenados as $encabezado) {
            $sheet->setCellValue($columnaLetra . '1', $encabezado);
            $columnaLetra++;
        }

        // Escribimos los datos
        $filaNumero = 2;
        foreach ($filasAgrupadas as $filaDatos) {
            $columnaLetra = 'A';
            foreach ($encabezadosOrdenados as $encabezado) {
                // Si la fila tiene un valor para este encabezado, lo escribimos. Si no, dejamos la celda vacía.
                $valor = $filaDatos[$encabezado] ?? '';
                $sheet->setCellValue($columnaLetra . $filaNumero, $valor);
                $columnaLetra++;
            }
            $filaNumero++;
        }

        // 6. FORZAR LA DESCARGA DEL ARCHIVO
        $nombreUsuario = $expediente->datosUnificados->first()->user->name ?? 'Usuario';
        $nombreArchivo = "Expediente_{$expediente->numero_expediente}_{$nombreUsuario}.xlsx";
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}