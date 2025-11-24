<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{User, ColumnaMaestra, DatoUnificado, Expediente, Constancia, AccionUsuario, TipoDocumento};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ExpedienteExport; // 🔥 Lo crearemos después
use Illuminate\Support\Facades\Validator; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Controllers\Traits\GeneratesConstanciaPdf;

class GeneradorController extends Controller
{
    use GeneratesConstanciaPdf;
    /**
     * PASO 1: Muestra la página inicial para buscar un usuario.
     */
    public function index()
    {
        $usuarios = User::orderBy('name')->get();
        return view('generador.index', compact('usuarios'));
    }

    /**
     * PASO 2: Busca los datos disponibles para un usuario y muestra el formulario de filtro.
     */
    public function buscarDatos(Request $request)
    {
        $validated = $request->validate(['user_id' => 'required|exists:users,id']);
        $usuario = User::findOrFail($validated['user_id']);
        
        // --- 🔥 INICIO DEL CAMBIO ---

        // 1. Obtenemos TODAS las columnas maestras del sistema, ya no filtramos por usuario.
        $todasLasColumnas = ColumnaMaestra::orderBy('nombre_display', 'asc')->get();

        // 2. Las separamos en dos listas usando el flag 'es_fijo' de la base de datos.
        //    Esto es más robusto que usar un array de nombres en la vista.
        $columnasFijas = $todasLasColumnas->where('es_fijo', true);
        $columnasOpcionales = $todasLasColumnas->where('es_fijo', false);
        
        // 3. Mantenemos esta consulta para el resumen informativo, es correcta.
        $columnasConDatosCount = ColumnaMaestra::whereHas('datos', function ($query) use ($usuario) {
            $query->where('user_id', $usuario->id);
        })->count();

        // --- 🔥 FIN DEL CAMBIO ---

        // La lógica para obtener los años disponibles no necesita cambios, está bien.
        $columnaAnio = ColumnaMaestra::where('nombre_normalizado', 'ano')->first();
        $aniosDisponibles = collect();
        if ($columnaAnio) {
            $aniosDisponibles = DatoUnificado::where('user_id', $usuario->id)
                ->where('columna_maestra_id', $columnaAnio->id)
                ->distinct()->orderBy('valor', 'desc')->pluck('valor');
        }

        return view('generador.filtros', [
            'usuario' => $usuario,
            'aniosDisponibles' => $aniosDisponibles,
            // --- 🔥 Pasamos las nuevas variables a la vista ---
            'columnasFijas' => $columnasFijas,
            'columnasOpcionales' => $columnasOpcionales,
            'columnasConDatosCount' => $columnasConDatosCount,
        ]);
    }
    
    /**
    * PASO 3: Muestra una previsualización de la tabla que se generará en el Excel.
    */
    public function previsualizar(Request $request)
    {
        // 1. Validar los datos del formulario de filtros (SIN CAMBIOS)
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'columnas' => 'required|array|min:1',
            'columnas.*' => 'exists:columnas_maestras,id',
        ]);
        $tiposDeDocumento = TipoDocumento::orderBy('nombre')->get();
        
        $usuario = User::findOrFail($validated['user_id']);
        $fechaDesde = Carbon::parse($validated['fecha_desde']);
        $fechaHasta = Carbon::parse($validated['fecha_hasta']);
        $columnasSeleccionadasIds = $validated['columnas'];
        
        // 2. Obtener los modelos de las columnas seleccionadas
        // 🔥 CAMBIO: Quitamos el orderBy('id') porque vamos a aplicar un orden personalizado.
        $columnasSeleccionadas = ColumnaMaestra::whereIn('id', $columnasSeleccionadasIds)->get();

        // 🔥 NUEVO: LÓGICA DE ORDENAMIENTO FIJO AÑADIDA AQUÍ
        // Define los grupos de orden. Los nombres deben ser los 'nombre_normalizado'.
        $ordenInicio = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion'];
        $ordenFinal = ['ref_mov', 'reint_', 'neto_a_pagar'];

        $columnasOrdenadas = $columnasSeleccionadas->sortBy(function ($columna) use ($ordenInicio, $ordenFinal) {
            $nombre = $columna->nombre_normalizado;

            // Buscamos en el grupo de INICIO
            $posicionInicio = array_search($nombre, $ordenInicio);
            if ($posicionInicio !== false) {
                return $posicionInicio; // Devuelve 0, 1, 2...
            }

            // Buscamos en el grupo FINAL
            $posicionFinal = array_search($nombre, $ordenFinal);
            if ($posicionFinal !== false) {
                return 1000 + $posicionFinal; // Devuelve 1000, 1001...
            }

            // Si no está en INICIO ni en FINAL, es "Relleno".
            return 500;
        });
        // 🔥 FIN DE LA LÓGICA DE ORDENAMIENTO
        
        // 3. Obtener TODOS los datos relevantes de la base de datos (SIN CAMBIOS)
        $datosCrudos = DatoUnificado::where('user_id', $usuario->id)
            ->whereIn('columna_maestra_id', $columnasSeleccionadasIds)
            ->whereBetween('fecha_registro', [$fechaDesde, $fechaHasta])
            ->orderBy('fecha_registro', 'asc')
            ->get();
        
        // 4. "Pivotar" los datos: transformar la lista larga en una tabla (SIN CAMBIOS)
        $tablaPrevia = [];
        foreach ($datosCrudos as $dato) {
            // 🔥 LA LÍNEA CORREGIDA 🔥
            // Usamos id_fila_origen como la clave única para cada fila.
            $claveFila = $dato->id_fila_origen; 
            
            if (!isset($tablaPrevia[$claveFila])) {
                $tablaPrevia[$claveFila] = [
                    'fecha' => $dato->fecha_registro,
                    'datos' => []
                ];
            }
            $tablaPrevia[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        
        // El ordenamiento por fecha seguirá funcionando correctamente
        uasort($tablaPrevia, function ($a, $b) {
            return $a['fecha'] <=> $b['fecha'];
        });
        
        $observacionColumnaId = ColumnaMaestra::where('nombre_normalizado', 'observacion')->value('id');
        
        // 5. Pasar los datos a la nueva vista de previsualización
        return view('generador.previsualizacion', [
            'usuario' => $usuario,
            // 🔥 CAMBIO: Pasamos la colección YA ORDENADA a la vista.
            'columnas' => $columnasOrdenadas, 
            'tabla' => $tablaPrevia,
            'requestData' => $validated, 
            'observacionColumnaId' => $observacionColumnaId,
            'tiposDeDocumento' => $tiposDeDocumento,
        ]);
    }
    /**
     * PASO 4: Guarda el expediente y fuerza la descarga del archivo Excel.
     */

     public function generarFinal(Request $request)
     {
         // 1. VALIDACIÓN
         // La validación de unicidad de Laravel se encarga de prevenir duplicados
         $validated = $request->validate([
             'user_id' => 'required|exists:users,id',
             'fecha_desde' => 'required|date',
             'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
             'columnas' => 'required|array|min:1',
             'columnas.*' => 'exists:columnas_maestras,id',
             'numero_constancia' => 'required|string|max:255',
             'numero_expediente' => 'required|string|max:255|unique:expedientes,numero_expediente',
             'tipo_documento_id' => 'required|exists:tipo_documentos,id'
         ]);
 
         // 2. NORMALIZACIÓN DE ENTRADAS
         $numeroConstanciaNormalizado = strtoupper(preg_replace('/\s+/', '', trim($validated['numero_constancia'])));
         $numeroExpedienteNormalizado = strtoupper(preg_replace('/\s+/', '', trim($validated['numero_expediente'])));
         
         DB::beginTransaction();
         try {
             // 3. BUSCAR O CREAR LA CONSTANCIA
             $constancia = Constancia::firstOrCreate(
                 ['numero_constancia' => $numeroConstanciaNormalizado],
                 [
                     'user_id' => $validated['user_id'],
                     'tipo_documento_id' => $validated['tipo_documento_id'],
                 ]
             );
 
             // 4. CREAR EL NUEVO EXPEDIENTE
             $rangoFechas = Carbon::parse($validated['fecha_desde'])->format('d/m/Y') . ' - ' . Carbon::parse($validated['fecha_hasta'])->format('d/m/Y');
             $expediente = Expediente::create([
                 'constancia_id' => $constancia->id,
                 'numero_expediente' => $numeroExpedienteNormalizado,
                 'generado_por_user_id' => auth()->id(),
                 'rango_fechas_descripcion' => $rangoFechas,
             ]);
 
             // 5. OBTENER LOS DATOS CRUDOS NECESARIOS
             $usuario = User::findOrFail($validated['user_id']);
             $datosCrudos = DatoUnificado::where('user_id', $usuario->id)
                 ->whereIn('columna_maestra_id', $validated['columnas'])
                 ->whereBetween('fecha_registro', [$validated['fecha_desde'], $validated['fecha_hasta']])
                 ->orderBy('fecha_registro', 'asc')
                 ->get();
 
             // 6. VINCULAR LOS DATOS AL EXPEDIENTE
             $datosParaVincular = $datosCrudos->pluck('id')->map(function ($id) use ($expediente) {
                 return ['expediente_id' => $expediente->id, 'dato_unificado_id' => $id];
             });
             DB::table('expediente_datos')->insert($datosParaVincular->toArray());
 
             // 7. REGISTRAR LA ACCIÓN DE AUDITORÍA
             AccionUsuario::create([
                 'user_id' => auth()->id(),
                 'tipo_accion' => 'GENERACION_EXPEDIENTE',
                 'referencia_id' => $expediente->id,
                 'referencia_tipo' => Expediente::class,
             ]);
 
             // Si todo ha ido bien hasta ahora, guardamos los cambios en la base de datos
             DB::commit();
 
             // 🔥 ========================================================== 🔥
             // 🔥 8. GENERAR Y DESCARGAR EL PDF USANDO EL TRAIT 🔥
             // Obtenemos el usuario que está generando el documento en este momento
             $generadoPor = auth()->user();
             
             // Llamamos a la función del Trait para generar y devolver la descarga del PDF
             return $this->streamConstanciaPdf($expediente, $usuario, $generadoPor, $datosCrudos);
             // 🔥 ========================================================== 🔥
 
         } catch (\Illuminate\Validation\ValidationException $e) {
             // Este catch es específico para los errores de validación de Laravel
             DB::rollBack();
             return redirect()->back()->withErrors($e->errors())->withInput();
             
         } catch (\Exception $e) {
             // Este catch es para cualquier otro error inesperado (ej. fallo de la base de datos)
             DB::rollBack();
             return redirect()->back()->with('error', 'Ocurrió un error inesperado al generar el expediente: ' . $e->getMessage())->withInput();
         }
     }

    private function obtenerYProcesarDatosParaReporte(Request $request): array
    {
        $userId = $request->input('user_id');
        $fechaDesde = Carbon::parse($request->input('fecha_desde'));
        $fechaHasta = Carbon::parse($request->input('fecha_hasta'));
        $columnasSeleccionadasIds = $request->input('columnas');

        // Lógica de Ordenamiento (Sin cambios)
        $columnasSeleccionadas = ColumnaMaestra::whereIn('id', $columnasSeleccionadasIds)->get();
        $ordenInicio = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion'];
        $ordenFinal = ['reint_', 'neto_a_pagar'];
        $columnasOrdenadas = $columnasSeleccionadas->sortBy(function ($columna) use ($ordenInicio, $ordenFinal) {
            $nombre = $columna->nombre_normalizado;
            $posicionInicio = array_search($nombre, $ordenInicio);
            if ($posicionInicio !== false) return $posicionInicio;
            $posicionFinal = array_search($nombre, $ordenFinal);
            if ($posicionFinal !== false) return 1000 + $posicionFinal;
            return 500;
        });

        // Obtener datos crudos
        $datosCrudos = DatoUnificado::where('user_id', $userId)
            ->whereIn('columna_maestra_id', $columnasSeleccionadasIds)
            ->whereBetween('fecha_registro', [$fechaDesde, $fechaHasta])
            ->orderBy('fecha_registro', 'asc')
            ->get();

        // Pivotar datos
        $tablaPivoteada = [];
        foreach ($datosCrudos as $dato) {
            // 🔥 LA LÍNEA CORREGIDA 🔥
            // Usamos id_fila_origen como la clave única para cada fila del reporte.
            $claveFila = $dato->id_fila_origen; 
            
            if (!isset($tablaPivoteada[$claveFila])) {
                $tablaPivoteada[$claveFila] = ['fecha' => $dato->fecha_registro, 'datos' => []];
            }
            $tablaPivoteada[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        
        // El ordenamiento por fecha seguirá funcionando perfectamente.
        uasort($tablaPivoteada, fn($a, $b) => $a['fecha'] <=> $b['fecha']);
    
        // Devolvemos todo lo que necesitamos
        return [$columnasOrdenadas, $tablaPivoteada, $datosCrudos];
    }
    public function verificarExistenciaExpediente(Request $request)
    {
        // 1. Validar que recibimos el parámetro necesario
        $validated = $request->validate([
            'numero_expediente' => 'required|string|max:255',
        ]);

        // 2. Normalizar el número (igual que en generarFinal)
        $numeroExpedienteNormalizado = strtoupper(preg_replace('/\s+/', '', trim($validated['numero_expediente'])));

        // 3. Buscar si existe en la base de datos
        $existe = Expediente::where('numero_expediente', $numeroExpedienteNormalizado)->exists();

        // 4. Retornar respuesta JSON
        return response()->json([
            'existe' => $existe,
            'numero' => $numeroExpedienteNormalizado
        ]);
    }
    
}