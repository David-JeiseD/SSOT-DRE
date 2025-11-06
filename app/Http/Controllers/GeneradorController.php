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
        // 1. Validar los datos del formulario de filtros
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
        
        // 2. Obtener los nombres de las columnas que necesitamos mostrar
        $columnasSeleccionadas = ColumnaMaestra::whereIn('id', $columnasSeleccionadasIds)
            ->orderBy('id') // Ordenar por ID o por un campo de orden si lo tuvieras
            ->get();
        
        // 3. Obtener TODOS los datos relevantes de la base de datos
        $datosCrudos = DatoUnificado::where('user_id', $usuario->id)
            ->whereIn('columna_maestra_id', $columnasSeleccionadasIds)
            ->whereBetween('fecha_registro', [$fechaDesde, $fechaHasta])
            ->orderBy('fecha_registro', 'asc')
            ->get();
        
        // 4. "Pivotar" los datos: transformar la lista larga en una tabla
        $tablaPrevia = [];
        foreach ($datosCrudos as $dato) {
            // 🔥 CORRECCIÓN: La clave de agrupación es AHORA SOLO LA FECHA.
            $claveFila = $dato->fecha_registro->format('Y-m'); 
            
            if (!isset($tablaPrevia[$claveFila])) {
                $tablaPrevia[$claveFila] = [
                    'fecha' => $dato->fecha_registro,
                    'datos' => []
                ];
            }
            
            // Si ya existe un valor para esta columna en este mes, lo concatenamos (o lo sobreescribimos).
            // Para la vista, sobreescribir suele ser suficiente.
            $tablaPrevia[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
        
        // Ordenar la tabla final por fecha
        uasort($tablaPrevia, function ($a, $b) {
            return $a['fecha'] <=> $b['fecha'];
        });
        
        // 🔥 NUEVO: Obtener el ID de la columna Observacion
        $observacionColumnaId = ColumnaMaestra::where('nombre_normalizado', 'observacion')->value('id');
        
        // 5. Pasar los datos a la nueva vista de previsualización
        return view('generador.previsualizacion', [
            'usuario' => $usuario,
            'columnas' => $columnasSeleccionadas,
            'tabla' => $tablaPrevia,
            'requestData' => $validated, 
            'observacionColumnaId' => $observacionColumnaId, // <-- Pasamos el ID a la vista
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