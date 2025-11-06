<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function buscar(Request $request)
    {
        $searchTerm = $request->query('q');

        if (empty($searchTerm)) {
            return response()->json([]);
        }

        $usuarios = \App\Models\User::where('name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('dni', 'LIKE', "%{$searchTerm}%")
                                ->select('id', 'name', 'dni') // Solo devolvemos los datos necesarios
                                ->take(10) // Limitamos a 10 resultados para que sea rápido
                                ->get();

        return response()->json($usuarios);
    }
}
