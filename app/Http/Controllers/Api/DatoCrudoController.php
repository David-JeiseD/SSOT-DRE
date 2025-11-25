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
    
        DB::beginTransaction();
        try {
            $filaDeReferencia = DatoUnificado::where('id_fila_origen', $idFilaOrigen)->firstOrFail();
            $datos = $request->input('datos');

            // 🔥 LÓGICA DE RECÁLCULO INTELIGENTE 🔥
            $columnasClave = ColumnaMaestra::whereIn('nombre_normalizado', [
                'total_remuneracion', 'total_descuento', 'reint_', 'neto_a_pagar'
            ])->pluck('id', 'nombre_normalizado');

            $idRemun = $columnasClave->get('total_remuneracion');
            $idDesc = $columnasClave->get('total_descuento');
            $idReint = $columnasClave->get('reint_');
            $idLiquido = $columnasClave->get('neto_a_pagar');

            // ¿Se modificó uno de los componentes de la fórmula?
            if (isset($datos[$idRemun]) || isset($datos[$idDesc]) || isset($datos[$idReint])) {
                
                // Obtenemos los valores más actuales, ya sea de la petición o de la BD
                $valorRemun = isset($datos[$idRemun]) ? (float)$datos[$idRemun] : (float)DatoUnificado::where('id_fila_origen', $idFilaOrigen)->where('columna_maestra_id', $idRemun)->value('valor');
                $valorDesc = isset($datos[$idDesc]) ? (float)$datos[$idDesc] : (float)DatoUnificado::where('id_fila_origen', $idFilaOrigen)->where('columna_maestra_id', $idDesc)->value('valor');
                $valorReint = isset($datos[$idReint]) ? (float)$datos[$idReint] : (float)DatoUnificado::where('id_fila_origen', $idFilaOrigen)->where('columna_maestra_id', $idReint)->value('valor');
                
                // Calculamos y sobrescribimos el valor de LÍQUIDO en los datos a guardar
                if ($idLiquido) {
                    $datos[$idLiquido] = ($valorRemun + $valorReint) - $valorDesc;
                }
            }
    
            // Lógica de guardado y auditoría
            foreach ($datos as $columnaId => $nuevoValor) {
                $dato = DatoUnificado::firstOrNew([
                    'id_fila_origen'     => $idFilaOrigen,
                    'columna_maestra_id' => $columnaId,
                ]);
    
                $valorAntiguo = $dato->valor;
    
                if (is_null($nuevoValor) || $nuevoValor === '') {
                    if ($dato->exists) $dato->delete();
                } else {
                    $dato->user_id = $filaDeReferencia->user_id;
                    $dato->fecha_registro = $filaDeReferencia->fecha_registro;
                    $dato->valor = $nuevoValor;
                    $dato->save();
                }
    
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
            }
    
            DB::commit();
            
            return response()->json([
                'message' => 'Fila actualizada con éxito', 
                'datos' => $datos
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