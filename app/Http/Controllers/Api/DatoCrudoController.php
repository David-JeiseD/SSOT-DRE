<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatoUnificado;
use App\Models\AccionUsuario;
use App\Models\ColumnaMaestra; // 🔥 Importante
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DatoCrudoController extends Controller
{
    /**
     * Actualiza los valores de una fila de datos.
     */
    public function update(Request $request, $idFilaOrigen)
    {
        // ... la validación no cambia ...
        $validator = Validator::make($request->all(), [
            'datos' => 'required|array',
            'datos.*' => 'nullable|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        DB::beginTransaction();
        try {
            $filaDeReferencia = DatoUnificado::where('id_fila_origen', $idFilaOrigen)->first();
            if (!$filaDeReferencia) {
                throw new \Exception('La fila que intentas editar ya no existe.');
            }
    
            foreach ($request->input('datos') as $columnaId => $nuevoValor) {
                
                // 🔥 ========================================================== 🔥
                // 🔥 LÓGICA REFINADA: Encuentra el registro o prepara uno nuevo en memoria.
                // firstOrNew: Lo busca. Si no lo encuentra, crea una NUEVA INSTANCIA, pero NO la guarda en la BD todavía.
                $dato = DatoUnificado::firstOrNew([
                    'id_fila_origen'     => $idFilaOrigen,
                    'columna_maestra_id' => $columnaId,
                ]);
    
                $valorAntiguo = $dato->valor; // Guardamos el valor antiguo para el log ANTES de cualquier cambio.
    
                // CASO 1: El nuevo valor está vacío.
                if (is_null($nuevoValor) || $nuevoValor === '') {
                    // Si el registro ya existía en la BD, lo eliminamos.
                    if ($dato->exists) {
                        $dato->delete();
                        // (Aquí podrías agregar un log de auditoría para la eliminación si lo necesitas)
                    }
                    // Si el registro no existía (era una instancia nueva), simplemente no hacemos nada y se descarta.
    
                // CASO 2: El nuevo valor NO está vacío.
                } else {
                    // Llenamos el modelo con los datos y lo guardamos.
                    // Esto funciona tanto para actualizar uno existente como para crear uno nuevo.
                    $dato->user_id = $filaDeReferencia->user_id;
                    $dato->fecha_registro = $filaDeReferencia->fecha_registro;
                    $dato->valor = $nuevoValor;
                    $dato->save();
                }
    
                // 🔥 AUDITORÍA: Se activa solo si el valor realmente cambió.
                // Comparamos el valor original con el nuevo.
                if ($valorAntiguo != $nuevoValor) {
                     AccionUsuario::create([
                        'user_id' => auth()->id(),
                        'tipo_accion' => 'EDICION_DATO_CRUDO',
                        'referencia_id' => $dato->id ?? $filaDeReferencia->id, // Usamos el id del dato si existe
                        'referencia_tipo' => DatoUnificado::class,
                        'metadata' => [
                            'afectado_user_id' => $filaDeReferencia->user_id,
                            'cambio' => [
                                'columna_maestra_id' => $columnaId,
                                'valor_anterior' => $valorAntiguo,
                                'valor_nuevo' => $nuevoValor, // Puede ser vacío, registrando la eliminación
                            ]
                        ],
                    ]);
                }
                // 🔥 ========================================================== 🔥
            }
    
            DB::commit();
            
            return response()->json([
                'message' => 'Fila actualizada con éxito', 
                'datos' => $request->input('datos')
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar la fila: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina todos los registros de una fila de datos.
     */
    public function destroy($idFilaOrigen)
    {
        DB::beginTransaction();
        try {
            $datosParaEliminar = DatoUnificado::with('columnaMaestra')->where('id_fila_origen', $idFilaOrigen)->get();
            if ($datosParaEliminar->isEmpty()) throw new \Exception('No se encontraron datos para la fila.');
    
            $resumenParaLog = [
                'afectado_user_id' => $datosParaEliminar->first()->user_id,
                'mes_pago' => Carbon::parse($datosParaEliminar->first()->fecha_registro)->format('m/Y'),
                'datos_eliminados' => $datosParaEliminar->mapWithKeys(fn($item) => [
                    $item->columnaMaestra->nombre_display => (string) $item->valor
                ])
            ];
    
            AccionUsuario::create([
                'user_id' => auth()->id(),
                'tipo_accion' => 'ELIMINACION_DATO_CRUDO',
                'referencia_id' => $resumenParaLog['afectado_user_id'],
                'referencia_tipo' => \App\Models\User::class,
                'metadata' => $resumenParaLog,
            ]);
    
            DatoUnificado::where('id_fila_origen', $idFilaOrigen)->delete();
            DB::commit();
            return response()->json(['message' => 'Fila eliminada con éxito']);
        }  catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar la fila: ' . $e->getMessage()], 500);
        }
    }
}