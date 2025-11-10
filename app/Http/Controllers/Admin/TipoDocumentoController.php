<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoDocumento;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    /**
     * Muestra la lista de todos los tipos de documento.
     */
    public function index()
    {
        // Usamos withCount para que Eloquent cuente las constancias de forma eficiente
        $tipos = TipoDocumento::withCount('constancias')->orderBy('nombre', 'asc')->get();
        
        // Pasamos los datos a la vista
        return view('admin.tipos-documento.index', compact('tipos'));
    }

    /**
     * Guarda un nuevo tipo de documento en la base de datos.
     */
    public function store(Request $request)
    {
        // Validación de los datos
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_documentos,nombre',
            'descripcion' => 'nullable|string',
        ]);

        TipoDocumento::create($validated);

        return redirect()->route('admin.tipos-documento.index')
            ->with('success', '¡Tipo de documento creado exitosamente!');
    }

    /**
     * Actualiza un tipo de documento existente.
     */
    public function update(Request $request, TipoDocumento $tipos_documento)
    {
        // El nombre de la variable $tipos_documento debe coincidir con el nombre de la ruta resource.
        $validated = $request->validate([
            // La regla unique ignora el ID actual para permitir cambiar otros campos
            'nombre' => 'required|string|max:255|unique:tipo_documentos,nombre,' . $tipos_documento->id,
            'descripcion' => 'nullable|string',
        ]);

        $tipos_documento->update($validated);

        return redirect()->route('admin.tipos-documento.index')
            ->with('success', '¡Tipo de documento actualizado exitosamente!');
    }

    /**
     * Elimina un tipo de documento.
     */
    public function destroy(TipoDocumento $tipos_documento)
    {
        // Opcional: Añadir una comprobación para no eliminar si tiene constancias asociadas
        if ($tipos_documento->constancias()->count() > 0) {
            return redirect()->route('admin.tipos-documento.index')
                ->with('error', 'No se puede eliminar un tipo que tiene constancias asociadas.');
        }

        $tipos_documento->delete();

        return redirect()->route('admin.tipos-documento.index')
            ->with('success', '¡Tipo de documento eliminado exitosamente!');
    }
}