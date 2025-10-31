<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IngestaService; // <-- Importamos nuestro futuro servicio
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Support\Str;
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
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $usuarioDeDatos = User::firstOrCreate(
            ['dni' => $validated['dni']],
            [
                'name' => $validated['nombre'], 
                'email' => $validated['dni'].'@placeholder.com', 
                'password' => bcrypt(Str::random(10))
            ]
        );

        try {
            $resultado = $this->ingestaService->procesarArchivo(
                $request->file('archivo'),
                $usuarioDeDatos,
                auth()->user()
            );

            // --- 🔥 MODIFICADO: Usamos las nuevas claves para un mensaje más claro ---
            return redirect()->route('ingesta.create')
                ->with('success', "Datos procesados. Se crearon {$resultado['creados']} nuevos registros, se actualizaron {$resultado['actualizados']} y se omitieron {$resultado['omitidos']} duplicados.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage())->withInput();
        }
    }
}
