<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Constancia;

class ConstanciaController extends Controller
{
    public function buscar(Request $request)
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $constancias = Constancia::where('numero_constancia', 'LIKE', "%{$query}%")
            ->select('id', 'numero_constancia')
            ->distinct('numero_constancia') // Para no mostrar duplicados
            ->take(10) // Limitar resultados
            ->get();

        return response()->json($constancias);
    }
}