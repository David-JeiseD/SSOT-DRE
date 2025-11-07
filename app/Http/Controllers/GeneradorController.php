<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ColumnaMaestra;
use App\Models\DatoUnificado;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeneradorController extends Controller
{
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
        $columnaAnio = ColumnaMaestra::where('nombre_normalizado', 'ano')->first();
        $aniosDisponibles = collect();
        if ($columnaAnio) {
            $aniosDisponibles = DatoUnificado::where('user_id', $usuario->id)
                ->where('columna_maestra_id', $columnaAnio->id)
                ->distinct()->orderBy('valor', 'desc')->pluck('valor');
        }
        $columnasDisponibles = ColumnaMaestra::whereHas('datos', function ($query) use ($usuario) {
            $query->where('user_id', $usuario->id);
        })->orderBy('nombre_display')->get();
        return view('generador.filtros', compact('usuario', 'aniosDisponibles', 'columnasDisponibles'));
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
            $claveFila = $dato->fecha_registro->format('Y-m'); 
            if (!isset($tablaPrevia[$claveFila])) {
                $tablaPrevia[$claveFila] = [
                    'fecha' => $dato->fecha_registro,
                    'datos' => []
                ];
            }
            $tablaPrevia[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        
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
        ]);
    }
    /**
     * PASO 4: Guarda el expediente y fuerza la descarga del archivo Excel.
     */
    public function generarFinal(Request $request)
    {
        dd('Llegamos al PASO 4: Generación Final. Datos recibidos:', $request->all());
    }
}