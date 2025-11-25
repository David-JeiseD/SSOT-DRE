<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IngestaService; // <-- Importamos nuestro futuro servicio
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Support\Str;
use App\Events\IngestaCompletada;

class IngestaController extends Controller
{
    protected $ingestaService;

    // Inyectamos el servicio en el constructor
    public function __construct(IngestaService $ingestaService)
    {
        $this->ingestaService = $ingestaService;
    }

    /**
     * Muestra el formulario para subir un nuevo archivo.
     */
    public function create()
    {
        return view('ingesta.create');
    }

    /**
     * Almacena y procesa el archivo subido.
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|digits:8',
            'nombre' => 'required|string|max:255',  
            'codigomodular' => 'nullable|string|max:255',
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $usuarioDeDatos = User::firstOrCreate(
            ['dni' => $validated['dni']],
            [
                'name' => $validated['nombre'], 
                'codigomodular' => $validated['codigomodular'],
                'email' => $validated['dni'].'@placeholder.com', 
                'password' => bcrypt(Str::random(10))
            ]
        );
        if (!$usuarioDeDatos->wasRecentlyCreated && $validated['codigomodular']) {
            $usuarioDeDatos->codigomodular = $validated['codigomodular'];
            $usuarioDeDatos->save();
        }


        try {
            $resultado = $this->ingestaService->procesarArchivo(
                $request->file('archivo'),
                $usuarioDeDatos,
                auth()->user()
            );

            IngestaCompletada::dispatch();
            
            $mensaje = "Archivo procesado. Se procesaron {$resultado['filas_procesadas']} filas nuevas. " .
                   "Se crearon un total de {$resultado['registros_creados']} registros individuales. " .
                   "Se omitieron {$resultado['filas_omitidas']} filas por ser duplicadas o no significativas.";

            return redirect()->route('ingesta.create')->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage())->withInput();
        }
    }
}
