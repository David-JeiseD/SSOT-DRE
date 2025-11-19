<?php

namespace App\Http\Controllers;

use App\Models\Constancia;
use App\Models\Expediente;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\ColumnaMaestra;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf; // <-- 🔥 ¡Añade este!
use Carbon\Carbon; 

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
        // 1. Auditoría
        auth()->user()->acciones()->create([
            'tipo_accion' => 'DESCARGA_EXPEDIENTE',
            'referencia_id' => $expediente->id,
            'referencia_tipo' => Expediente::class,
        ]);

        // 2. OBTENER DATOS Y USUARIO
        $expediente->load('datosUnificados.columnaMaestra', 'constancia.user');
        $datosCrudos = $expediente->datosUnificados;
        $usuario = $expediente->constancia->user;
        
        // ==========================================================
        // INICIO DE LA LÓGICA DE ORDENAMIENTO CORRECTA
        // ==========================================================

        // 3. OBTENER Y ORDENAR LOS ENCABEZADOS (HEADERS)
        $columnas = $datosCrudos->pluck('columnaMaestra')->unique('id');
        
        // Definimos el orden exacto (igual que en GeneradorController)
        $ordenInicio = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion'];
        $ordenFinal = ['ref_mov', 'reint_', 'neto_a_pagar'];
        
        $columnasOrdenadas = $columnas->sortBy(function ($columna) use ($ordenInicio, $ordenFinal) {
            $nombre = $columna->nombre_normalizado;
            $posicionInicio = array_search($nombre, $ordenInicio);
            if ($posicionInicio !== false) return $posicionInicio;
            $posicionFinal = array_search($nombre, $ordenFinal);
            if ($posicionFinal !== false) return 1000 + $posicionFinal;
            return 500; // Columnas sin orden específico van al medio
        });

        // 4. PIVOTAR LOS DATOS (agrupar por fila)
        $tablaPivoteada = [];
        foreach ($datosCrudos as $dato) {
            $claveFila = $dato->id_fila_origen;
            if (!isset($tablaPivoteada[$claveFila])) {
                $tablaPivoteada[$claveFila] = ['fecha' => $dato->fecha_registro, 'datos' => []];
            }
            $tablaPivoteada[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        // Ordenamos las filas por fecha
        uasort($tablaPivoteada, fn($a, $b) => $a['fecha'] <=> $b['fecha']);
        
        // ==========================================================
        // FIN DE LA LÓGICA DE ORDENAMIENTO
        // ==========================================================

        // 5. GENERAR EL ARCHIVO EXCEL
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Escribir cabeceras ordenadas
        $columnaLetra = 'A';
        foreach ($columnasOrdenadas as $columna) {
            $sheet->setCellValue($columnaLetra . '1', $columna->nombre_display);
            $columnaLetra++;
        }

        // Escribir filas de datos
        $filaNumero = 2;
        foreach ($tablaPivoteada as $filaDatos) {
            $columnaLetra = 'A';
            foreach ($columnasOrdenadas as $columna) {
                $valor = $filaDatos['datos'][$columna->id] ?? '';
                $sheet->setCellValue($columnaLetra . $filaNumero, $valor);
                $columnaLetra++;
            }
            $filaNumero++;
        }

        // 6. FORZAR LA DESCARGA
        $nombreUsuario = Str::slug($usuario->name); // Usamos Str::slug para un nombre de archivo seguro
        $nombreArchivo = "Expediente_{$expediente->numero_expediente}_{$nombreUsuario}.xlsx";
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function show(Expediente $expediente)
    {
        // 1. Cargamos las relaciones para obtener el usuario y la constancia
        $expediente->load('constancia.user', 'datosUnificados.columnaMaestra');

        // 2. "Pivotamos" los datos del expediente para convertirlos en una tabla fácil de leer
        $datosCrudos = $expediente->datosUnificados;

        // 3. Obtenemos solo las columnas que están presentes en este expediente y las ordenamos
        $columnasIds = $datosCrudos->pluck('columna_maestra_id')->unique();
        $columnas = ColumnaMaestra::find($columnasIds);

        // Reutilizamos la lógica de ordenamiento
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

        // Pivotamos la tabla
        $tabla = [];
        foreach ($datosCrudos as $dato) {
            $claveFila = $dato->id_fila_origen;
            if (!isset($tabla[$claveFila])) {
                $tabla[$claveFila] = ['fecha' => $dato->fecha_registro, 'datos' => []];
            }
            $tabla[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        uasort($tabla, fn($a, $b) => $a['fecha'] <=> $b['fecha']);

        // 4. Pasamos toda la información a la nueva vista
        return view('expedientes.show', [
            'expediente' => $expediente,
            'columnas' => $columnasOrdenadas,
            'tabla' => $tabla
        ]);
    }
    public function descargarPdf(Expediente $expediente)
    {
        // 1. Registramos la auditoría de descarga
        auth()->user()->acciones()->create([
            'tipo_accion' => 'DESCARGA_CONSTANCIA_PDF', // Acción más específica
            'referencia_id' => $expediente->id,
            'referencia_tipo' => Expediente::class,
        ]);

        // 2. Cargamos todas las relaciones necesarias
        $expediente->load('constancia.user', 'datosUnificados.columnaMaestra', 'generadoPor');

        // 3. Obtenemos las variables principales
        $usuario = $expediente->constancia->user;
        $generadoPor = auth()->user(); // O $expediente->generadoPor si quieres al creador original
        $datosCrudos = $expediente->datosUnificados;

        // 4. Obtenemos y ordenamos las columnas (lógica ya conocida)
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

        // 5. Pivotamos la tabla (lógica ya conocida)
        $tabla = [];
        foreach ($datosCrudos as $dato) {
            $claveFila = $dato->id_fila_origen;
            if (!isset($tabla[$claveFila])) {
                $tabla[$claveFila] = ['fecha' => $dato->fecha_registro, 'datos' => []];
            }
            $tabla[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        uasort($tabla, fn($a, $b) => $a['fecha'] <=> $b['fecha']);

        // 6. Juntamos todos los datos que la vista del PDF necesita
        $data = [
            'expediente' => $expediente,
            'usuario' => $usuario,
            'generadoPor' => $generadoPor,
            'columnas' => $columnasOrdenadas,
            'tabla' => $tabla
        ];

        // 7. Generamos el PDF
        $pdf = Pdf::loadView('pdf.constancia', $data);

        // 8. Forzamos la descarga
        $nombreArchivo = "Constancia_{$expediente->numero_expediente}_{$usuario->dni}.pdf";
        return $pdf->download($nombreArchivo);
    }
}