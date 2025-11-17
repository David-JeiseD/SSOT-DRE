<?php

namespace App\Http\Controllers;

use App\Models\AccionUsuario;
use App\Models\Expediente;
use App\Models\Meta;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteMetasController extends Controller
{
    /**
     * Muestra la página de reportes de metas y procesa el formulario de búsqueda.
     */
    public function index(Request $request)
    {
        // Se obtienen todos los usuarios con el rol 'encargado' para el selector del formulario
        $encargados = User::role('encargado')->orderBy('name')->get();

        // Inicializamos las variables que se pasarán a la vista
        $resultados = null;
        $mensajeMeta = '';
        $usuarioSeleccionado = null;
        $metaDelMes = 0; // Variable clave para la vista

        // Este bloque solo se ejecuta si el formulario ha sido enviado con los datos necesarios
        if ($request->has('user_id') && $request->has('year') && $request->has('month')) {
            
            // 1. Validación de los datos de entrada
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'year'    => 'required|integer|min:2020',
                'month'   => 'required|integer|between:1,12',
            ]);

            // 2. Obtención de los datos validados
            $userId = $request->input('user_id');
            $year   = $request->input('year');
            $month  = $request->input('month');
            
            $usuarioSeleccionado = User::findOrFail($userId);

            // --- 🔥 INICIO DE LA LÓGICA ACTUALIZADA ---

            // 3. OBTENER EL KPI PRINCIPAL: Contamos los expedientes generados por el usuario en el periodo.
            // Esta es la métrica más importante y reemplaza el conteo general de "acciones".
            $expedientesGenerados = Expediente::where('generado_por_user_id', $userId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            // 4. OBTENER LA META ESTABLECIDA: Buscamos en la tabla 'metas' la cantidad definida.
            $metaEstablecida = Meta::where('user_id', $userId)
                ->where('anio', $year)
                ->where('mes', $month)
                ->value('cantidad'); // value() obtiene solo el valor de la columna o null si no existe.

            // Si no se encuentra una meta, se asume que es 0.
            $metaDelMes = $metaEstablecida ?? 0;

            // 5. GENERAR EL MENSAJE DE PROGRESO: Usamos las variables correctas ($expedientesGenerados y $metaDelMes).
            // Los textos ahora hablan de "expedientes" para ser coherentes.
            if ($metaDelMes > 0 && $expedientesGenerados >= $metaDelMes) {
                $mensajeMeta = "¡Meta cumplida! El usuario ha generado {$expedientesGenerados} de {$metaDelMes} expedientes este mes.";
            } elseif ($metaDelMes > 0) {
                $restantes = $metaDelMes - $expedientesGenerados;
                $mensajeMeta = "El usuario {$usuarioSeleccionado->name} aún no ha cumplido su meta. Le faltan {$restantes} expedientes para alcanzar los " . $metaDelMes . ".";
            } else {
                $mensajeMeta = "No se ha establecido una meta para este usuario en este periodo. Expedientes generados: {$expedientesGenerados}.";
            }

            // 6. OBTENER DATOS SECUNDARIOS: El log de actividad detallada para la tabla inferior.
            // Esto sigue siendo útil para auditoría.
            $accionesDetalladas = AccionUsuario::where('user_id', $userId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->latest()
                ->paginate(15);

            // 7. PREPARAR LOS DATOS PARA LA VISTA: Construimos el array de resultados.
            // 'totalGeneral' ahora contiene el KPI correcto. 'detalles' contiene el log.
            $resultados = [
                'totalGeneral' => $expedientesGenerados,
                'detalles'     => $accionesDetalladas
            ];
            
            // --- 🔥 FIN DE LA LÓGICA ACTUALIZADA ---
        }

        // 8. Devolver la vista con todas las variables necesarias
        return view('reportes.metas', [
            'encargados'          => $encargados,
            'resultados'          => $resultados,
            'mensajeMeta'         => $mensajeMeta,
            'usuarioSeleccionado' => $usuarioSeleccionado,
            'metaDelMes'          => $metaDelMes, // Pasamos esta variable para usarla en la vista
            'inputs'              => $request->all() // Para mantener los valores en los selectores del formulario
        ]);
    }
}