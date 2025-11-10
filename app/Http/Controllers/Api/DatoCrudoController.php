<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatoUnificado;
use App\Models\AccionUsuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DatoCrudoController extends Controller
{
    /**
     * Actualiza todos los valores de una fila de datos unificados.
     */
    public function update(Request $request, $idFilaOrigen)
    {
        // 1. VALIDACIÓN (Sin cambios)
        $validator = Validator::make($request->all(), [
            'datos' => 'required|array',
            'datos.*' => 'nullable|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $nuevosDatos = $request->input('datos');
    
        DB::beginTransaction();
        try {
            $datosOriginales = DatoUnificado::where('id_fila_origen', $idFilaOrigen)->get();
    
            foreach ($nuevosDatos as $columnaId => $nuevoValor) {
                $dato = $datosOriginales->firstWhere('columna_maestra_id', $columnaId);
    
                if ($dato) {
                    $valorAntiguo = $dato->valor;
    
                    if ($valorAntiguo != $nuevoValor) {
                        $dato->valor = $nuevoValor;
                        $dato->save();
    
                        // 🔥 EL CAMBIO ESTÁ AQUÍ 🔥
                        // Hemos reemplazado 'descripcion' por 'metadata'.
                        AccionUsuario::create([
                            'user_id' => auth()->id(),
                            'tipo_accion' => 'EDICION_DATO_CRUDO',
                            'referencia_id' => $dato->id,
                            'referencia_tipo' => DatoUnificado::class,
                            'metadata' => [ // Laravel convertirá este array a JSON
                                'valor_anterior' => $valorAntiguo,
                                'valor_nuevo' => $nuevoValor,
                                'id_fila_origen' => $idFilaOrigen,
                                'columna_maestra_id' => $columnaId,
                            ],
                        ]);
                    }
                }
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Fila actualizada con éxito',
                'datos' => $nuevosDatos
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar la fila: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina todos los registros de una fila de datos unificados.
     */
    public function destroy($idFilaOrigen)
    {
        DB::beginTransaction();
        try {
            $datosParaEliminar = DatoUnificado::where('id_fila_origen', $idFilaOrigen)->get();
    
            if ($datosParaEliminar->isEmpty()) {
                return response()->json(['message' => 'No se encontraron datos para esta fila.'], 404);
            }
    
            $datosEliminadosArray = $datosParaEliminar->toArray();
            $userIdDato = $datosParaEliminar->first()->user_id;
    
            // 🔥 EL CAMBIO ESTÁ AQUÍ 🔥
            // Hemos reemplazado 'descripcion' por la nueva columna 'metadata'.
            AccionUsuario::create([
                'user_id' => auth()->id(),
                'tipo_accion' => 'ELIMINACION_DATO_CRUDO',
                'referencia_id' => $userIdDato,
                'referencia_tipo' => \App\Models\User::class,
                'metadata' => [ // Laravel convertirá automáticamente este array a JSON
                    'id_fila_origen_eliminada' => $idFilaOrigen,
                    'datos_eliminados' => $datosEliminadosArray,
                ],
            ]);
    
            DatoUnificado::where('id_fila_origen', $idFilaOrigen)->delete();
    
            DB::commit();
    
            return response()->json(['message' => 'Fila eliminada con éxito']);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar la fila: ' . $e->getMessage()], 500);
        }
    }
}