<?php
// app/Http/Controllers/Admin/ColumnaMaestraController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ColumnaMaestra;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ColumnaMaestraController extends Controller
{
    public function index()
    {
        $columnas = ColumnaMaestra::orderBy('nombre_display')->paginate(10);
        return view('admin.columnas-maestras.index', compact('columnas'));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre_display' => 'required|string|max:255|unique:columnas_maestras,nombre_display',
                'nombre_normalizado' => 'required|string|max:255|unique:columnas_maestras,nombre_normalizado|regex:/^[a-z0-9_]+$/',
                'descripcion' => 'nullable|string',
                'es_fijo' => 'required|boolean',
            ]);

            // 🔥 CORRECCIÓN: Usamos $validatedData que solo contiene los campos validados.
            ColumnaMaestra::create($validatedData);

            return redirect()->route('admin.columnas-maestras.index')->with('success', 'Columna creada exitosamente.');

        } catch (ValidationException $e) {
            // Si la validación falla, redirigimos con una bandera para reabrir el modal de CREACIÓN.
            return redirect()->back()->withErrors($e->errors())->withInput()->with('error_form_type', 'create');
        }
    }

    public function update(Request $request, string $id)
    {
        // Buscamos el modelo manualmente
        $columnaMaestra = ColumnaMaestra::findOrFail($id);

        try {
            $validatedData = $request->validate([
                'nombre_display' => 'required|string|max:255|unique:columnas_maestras,nombre_display,' . $columnaMaestra->id,
                'nombre_normalizado' => 'required|string|max:255|regex:/^[a-z0-9_]+$/|unique:columnas_maestras,nombre_normalizado,' . $columnaMaestra->id,
                'descripcion' => 'nullable|string',
                'es_fijo' => 'required|boolean',
            ]);
            
            if ($columnaMaestra->es_fijo && ($columnaMaestra->nombre_normalizado != $validatedData['nombre_normalizado'])) {
                return back()->withInput()->withErrors(['nombre_normalizado' => 'No se puede cambiar el nombre normalizado de una columna fija.'])->with('error_form_type', 'edit');
            }

            $columnaMaestra->update($validatedData);

            return redirect()->route('admin.columnas-maestras.index')->with('success', 'Columna actualizada exitosamente.');

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput()->with('error_form_type', 'edit');
        }
    }

    public function destroy(string $id)
    {
        // 1. Buscamos el modelo manualmente. Si no lo encuentra, dará un error 404.
        $columnaMaestra = ColumnaMaestra::findOrFail($id);

        // 2. Ahora que SÍ tenemos el modelo correcto, las verificaciones funcionarán.
        if ($columnaMaestra->es_fijo) {
            return redirect()->back()->with('error', 'No se puede eliminar una columna maestra que es fija.');
        }
    
        if ($columnaMaestra->datos()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar la columna porque ya tiene datos asociados.');
        }
        
        // 3. Eliminamos y redirigimos.
        $columnaMaestra->delete();
    
        return redirect()->route('admin.columnas-maestras.index')
            ->with('success', '¡Columna eliminada exitosamente!');
    }
}