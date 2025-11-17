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
            if ($datosOriginales->isEmpty()) {
                throw new \Exception('No se encontraron datos para la fila a actualizar.');
            }

            // 🔥 OBTENEMOS EL ID DEL USUARIO UNA SOLA VEZ 🔥
            $usuarioAfectadoId = $datosOriginales->first()->user_id;

            foreach ($request->input('datos') as $columnaId => $nuevoValor) {
                $dato = $datosOriginales->firstWhere('columna_maestra_id', $columnaId);

                if ($dato && $dato->valor != $nuevoValor) {
                    $valorAntiguo = $dato->valor;
                    $dato->valor = $nuevoValor;
                    $dato->save();

                    AccionUsuario::create([
                        'user_id' => auth()->id(),
                        'tipo_accion' => 'EDICION_DATO_CRUDO',
                        'referencia_id' => $dato->id,
                        'referencia_tipo' => DatoUnificado::class,
                        'metadata' => [
                            // 🔥 AQUÍ ESTÁ LA CORRECCIÓN CRUCIAL 🔥
                            // Añadimos el ID del usuario afectado al metadata.
                            'afectado_user_id' => $usuarioAfectadoId,
                            'cambio' => [
                                'columna_maestra_id' => $columnaId,
                                'valor_anterior' => $valorAntiguo,
                                'valor_nuevo' => $nuevoValor,
                            ]
                        ],
                    ]);
                }
            }
            DB::commit();
            return response()->json(['message' => 'Fila actualizada con éxito', 'datos' => $request->input('datos')]);
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