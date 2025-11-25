<?php
// app/Http/Controllers/Admin/DatoGestionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DatoUnificado;
use App\Models\ColumnaMaestra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatoGestionController extends Controller
{
    /**
     * Mapeo de meses en español a números
     */
    protected $mesesEnEspanol = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'setiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];

    /**
     * Muestra la página inicial con el buscador de usuarios.
     */
    public function index()
    {
        return view('admin.gestion-datos.index');
    }

    /**
     * Muestra la tabla de datos para un usuario específico.
     */
    public function show(User $user)
    {
        // 1. Obtenemos todas las columnas
        $todasLasColumnas = ColumnaMaestra::get();
        
        // 2. Definimos el orden de visualización de los elementos
        $ordenInicio = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion'];
        $ordenFinal = ['reint_', 'neto_a_pagar']; // 'reint_' y 'liquido'
    
        $columnasOrdenadas = $todasLasColumnas->sortBy(function ($columna) use ($ordenInicio, $ordenFinal) {
            $nombre = $columna->nombre_normalizado;
    
            // Grupo de INICIO
            $posicionInicio = array_search($nombre, $ordenInicio);
            if ($posicionInicio !== false) {
                return $posicionInicio; // Ordena como 0, 1, 2, ...
            }
    
            // Grupo FINAL
            $posicionFinal = array_search($nombre, $ordenFinal);
            if ($posicionFinal !== false) {
                return 1000 + $posicionFinal; // Ordena como 1000, 1001, ... para ponerlos al final
            }
    
            // Columnas de "relleno" van en el medio
            return 500;
        });
    
        // 3. Obtenemos y pivotamos los datos del usuario (sin cambios)
        $datosCrudos = DatoUnificado::where('user_id', $user->id)
            ->orderBy('fecha_registro', 'desc')
            ->get();
        
        $tabla = [];
        foreach ($datosCrudos as $dato) {
            $claveFila = $dato->id_fila_origen; 
            if (!isset($tabla[$claveFila])) {
                $tabla[$claveFila] = ['fecha' => $dato->fecha_registro, 'datos' => []];
            }
            $tabla[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }
    
        // 4. Obtenemos los IDs para el cálculo (sin cambios)
        $idColumnasCalculo = ColumnaMaestra::whereIn('nombre_normalizado', [
            'total_remuneracion', 
            'total_descuento',
            'reint_',
            'neto_a_pagar'
        ])->pluck('id', 'nombre_normalizado');
    
        // 5. Devolvemos los datos a la vista
        return view('admin.gestion-datos.show', [
            'usuario' => $user,
            // 🔥 ========================================================== 🔥
            // 🔥 CORRECCIÓN: Quitamos el ->values() para preservar el orden 🔥
            'columnas' => $columnasOrdenadas,
            // 🔥 ========================================================== 🔥
            'tabla' => $tabla,
            'idColumnasCalculo' => $idColumnasCalculo,
        ]);
    }
    /**
     * Almacena un nuevo registro de pago completo para un usuario.
     */
    public function store(Request $request, User $user)
    {
        // 1. Buscamos los IDs de las columnas clave
        $columnasClave = ColumnaMaestra::whereIn('nombre_normalizado', [
            'ano', 'meses', 'total_remuneracion', 'total_descuento', 'reint_', 'neto_a_pagar'
        ])->pluck('id', 'nombre_normalizado');

        $colAnioId = $columnasClave->get('ano');
        $colMesesId = $columnasClave->get('meses');

        if (!$colAnioId || !$colMesesId) {
            return response()->json(['message' => 'Las columnas maestras "AÑO" o "MESES" no existen.'], 500);
        }

        // 2. Validamos los datos de entrada
        $request->validate([
            "datos.{$colAnioId}" => 'required|numeric|digits:4',
            "datos.{$colMesesId}" => 'required|string|max:50',
            'datos' => 'required|array|min:2',
        ]);

        $nuevosDatos = $request->input('datos');
        
        DB::beginTransaction();
        try {
            // 🔥 3. LÓGICA DE CÁLCULO PARA NUEVAS FILAS 🔥
            $remun = (float)($nuevosDatos[$columnasClave->get('total_remuneracion')] ?? 0);
            $desc = (float)($nuevosDatos[$columnasClave->get('total_descuento')] ?? 0);
            $reint = (float)($nuevosDatos[$columnasClave->get('reint_')] ?? 0);
            
            // Calculamos y añadimos el líquido al array de datos a guardar
            if ($columnasClave->has('neto_a_pagar')) {
                $nuevosDatos[$columnasClave->get('neto_a_pagar')] = ($remun + $reint) - $desc;
            }

            // 4. Creamos ID y fecha de registro
            $idFilaOrigen = (string) Str::uuid();
            $anio = $nuevosDatos[$colAnioId];
            $mes = strtolower(trim($nuevosDatos[$colMesesId]));
            
            if (!isset($this->mesesEnEspanol[$mes])) {
                throw new \Exception("El mes '{$mes}' no es válido.");
            }
            $numeroMes = $this->mesesEnEspanol[$mes];
            $fechaRegistro = Carbon::create($anio, $numeroMes, 1)->toDateString();

            // 5. Iteramos y guardamos cada nuevo dato
            $datosGuardados = [];
            foreach ($nuevosDatos as $columnaId => $valor) {
                if (!is_null($valor) && $valor !== '') {
                    DatoUnificado::create([
                        'user_id' => $user->id,
                        'columna_maestra_id' => $columnaId,
                        'valor' => $valor,
                        'fecha_registro' => $fechaRegistro,
                        'id_fila_origen' => $idFilaOrigen,
                    ]);
                    $datosGuardados[$columnaId] = $valor;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Registro creado exitosamente.',
                'id_fila_origen' => $idFilaOrigen,
                'datos' => $datosGuardados
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'No se pudo crear el registro: ' . $e->getMessage()], 500);
        }
    }
}
