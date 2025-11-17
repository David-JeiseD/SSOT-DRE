<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meta;
use App\Models\User;


class MetaController extends Controller
{
    public function index()
    {
        $encargados = User::role('encargado')->orderBy('name')->get();
        // Cargar las metas y agruparlas por usuario para una fácil visualización
        $metasPorUsuario = Meta::with('user')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get()
            ->groupBy('user_id');

        return view('admin.metas.index', compact('encargados', 'metasPorUsuario'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'anio' => 'required|integer|min:2020',
            'mes' => 'required|integer|between:1,12',
            'cantidad' => 'required|integer|min:0',
        ]);

        // updateOrCreate es perfecto: crea o actualiza si ya existe
        Meta::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'anio' => $request->anio,
                'mes' => $request->mes,
            ],
            [
                'cantidad' => $request->cantidad,
            ]
        );

        return back()->with('success', 'Meta guardada correctamente.');
    }
}