<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class ProfileController extends Controller
{
    // ... (tus otros métodos como update, etc.) ...

    /**
     * Mostrar el perfil del usuario (vista)
     */
    public function show()
    {
        $usuario = auth()->user();
    
        // 1. Obtener TODAS las metas del usuario (Esto ya estaba bien)
        $metasCrudas = $usuario->metas()
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'asc')
            ->get();
    
        // 2. Agrupar las metas (Esto ya estaba bien)
        $metasPorAnio = $metasCrudas->groupBy('anio')
            ->map(fn ($metasDelAnio) => $metasDelAnio->keyBy('mes'));
    
        // --- 🔥 INICIO DE LA CORRECCIÓN 🔥 ---

        // 3. Obtener SÓLO las acciones de generación, contadas y agrupadas
        $accionesCrudas = $usuario->acciones()
            ->where('tipo_accion', 'GENERACION_EXPEDIENTE') // Se añade el filtro aquí
            ->select(
                DB::raw('YEAR(created_at) as anio'),
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('count(*) as total')
            )
            ->groupBy('anio', 'mes')
            ->orderBy('anio', 'desc')
            ->get();
    
        // --- 🔥 FIN DE LA CORRECCIÓN 🔥 ---

        // 4. Agrupar las acciones (Esta lógica no cambia, ahora recibe los datos correctos)
        $accionesPorAnio = $accionesCrudas->groupBy('anio')
            ->map(fn ($accionesDelAnio) => $accionesDelAnio->pluck('total', 'mes'));
    
        return view('profile.show', compact(
            'usuario', 
            'metasPorAnio', 
            'accionesPorAnio'
        ));
    }
}