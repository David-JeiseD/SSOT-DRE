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
        $todasLasColumnas = ColumnaMaestra::get();
        
        // Define the exact order that matches your business logic
        $ordenInicio = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion'];
        $ordenFinal = ['ref_mov', 'reint_', 'neto_a_pagar'];

        $columnasOrdenadas = $todasLasColumnas->sortBy(function ($columna) use ($ordenInicio, $ordenFinal) {
            $nombre = $columna->nombre_normalizado;

            // Check in INICIO group
            $posicionInicio = array_search($nombre, $ordenInicio);
            if ($posicionInicio !== false) {
                return $posicionInicio;
            }

            // Check in FINAL group
            $posicionFinal = array_search($nombre, $ordenFinal);
            if ($posicionFinal !== false) {
                return 1000 + $posicionFinal;
            }

            // Everything else goes in the middle (filler columns)
            return 500;
        });

        // 2. Obtenemos todos los datos del usuario.
        $datosCrudos = DatoUnificado::where('user_id', $user->id)
            ->orderBy('fecha_registro', 'desc')
            ->get();

        // 3. Agrupamos los datos por 'id_fila_origen' (pivotamos)
        $tabla = [];
        foreach ($datosCrudos as $dato) {
            $claveFila = $dato->id_fila_origen; 
            
            if (!isset($tabla[$claveFila])) {
                $tabla[$claveFila] = [
                    'fecha' => $dato->fecha_registro,
                    'datos' => []
                ];
            }
            $tabla[$claveFila]['datos'][$dato->columna_maestra_id] = $dato->valor;
        }

        return view('admin.gestion-datos.show', [
            'usuario' => $user,
            'columnas' => $columnasOrdenadas->values(),
            'tabla' => $tabla,
        ]);
    }

    /**
     * Almacena un nuevo registro de pago completo para un usuario.
     */
    public function store(Request $request, User $user)
    {
        \Log::info('[v0] store() called', ['user_id' => $user->id, 'request_data' => $request->all()]);

        // 1. Buscamos los IDs de las columnas clave
        $colAnioId = ColumnaMaestra::where('nombre_normalizado', 'ano')->value('id');
        $colMesesId = ColumnaMaestra::where('nombre_normalizado', 'meses')->value('id');

        if (!$colAnioId || !$colMesesId) {
            \Log::error('[v0] Missing master columns', ['ano_id' => $colAnioId, 'meses_id' => $colMesesId]);
            return response()->json(['message' => 'Las columnas maestras "AÑO" o "MESES" no existen.'], 500);
        }

        // 2. Validamos los datos de entrada
        $validated = $request->validate([
            "datos.{$colAnioId}" => 'required|numeric|digits:4',
            "datos.{$colMesesId}" => 'required|string|max:50',
            'datos' => 'required|array|min:2',
        ]);

        $nuevosDatos = $request->input('datos');
        $anio = $nuevosDatos[$colAnioId];
        $mes = strtolower(trim($nuevosDatos[$colMesesId]));

        DB::beginTransaction();
        try {
            // 3. Creamos un nuevo ID único para esta fila
            $idFilaOrigen = (string) Str::uuid();

            // 4. Use Spanish month mapping instead of Carbon::parse() which fails with Spanish month names
            if (!isset($this->mesesEnEspanol[$mes])) {
                throw new \Exception("El mes '{$mes}' no es válido. Use nombres de mes en español (ej: enero, febrero, marzo, etc.)");
            }
            $numeroMes = $this->mesesEnEspanol[$mes];
            $fechaRegistro = Carbon::create($anio, $numeroMes, 1)->toDateString();

            \Log::info('[v0] Creating record', ['id_fila_origen' => $idFilaOrigen, 'fecha' => $fechaRegistro, 'mes' => $mes]);

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

            // 6. Devolvemos la fila creada para que el frontend la pueda mostrar
            return response()->json([
                'message' => 'Registro creado exitosamente.',
                'id_fila_origen' => $idFilaOrigen,
                'datos' => $datosGuardados
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('[v0] Error saving record', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'No se pudo crear el registro: ' . $e->getMessage()], 500);
        }
    }
}
