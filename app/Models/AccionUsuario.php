<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ColumnaMaestra;

class AccionUsuario extends Model
{
    use HasFactory;

    protected $table = 'acciones_usuario'; 
    
    protected $guarded = [];
    
    protected $casts = [
        'metadata' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function referencia() {
        return $this->morphTo();
    }
    
    // Este accesor ya lo tienes y es útil. Lo mantenemos.
    public function getNombreColumnaEditadaAttribute(): ?string
    {
        if ($this->tipo_accion === 'EDICION_DATO_CRUDO' && isset($this->metadata['cambio']['columna_maestra_id'])) {
            return ColumnaMaestra::find($this->metadata['cambio']['columna_maestra_id'])->nombre_display ?? 'Columna Desconocida';
        }
        return null;
    }

    // --- 🔥 INICIO DEL NUEVO ACCESOR INTELIGENTE 🔥 ---
    /**
     * Genera una descripción HTML completa y formateada de la acción.
     * Centraliza toda la lógica de presentación que antes estaba en la vista de detalle.
     *
     * @return string
     */
    public function getDescripcionHtmlAttribute(): string
    {
        // Usamos el helper e() para escapar cualquier dato del usuario y prevenir XSS.
        $e = fn($value) => e($value);

        switch ($this->tipo_accion) {
            case 'DESCARGA_EXPEDIENTE':
                $expediente = \App\Models\Expediente::find($this->referencia_id);
                if ($expediente) {
                    $numExp = $e($expediente->numero_expediente);
                    return "Descargó el expediente <code class=\"bg-gray-200 px-1 rounded font-semibold\">{$numExp}</code>.";
                }
                return 'Descargó un expediente que fue eliminado posteriormente.';
            case 'GENERACION_EXPEDIENTE':
                $expediente = Expediente::with('constancia')->find($this->referencia_id);
                if ($expediente) {
                    $numExp = $e($expediente->numero_expediente);
                    $numConst = $e(optional($expediente->constancia)->numero_constancia);
                    return "Generó el expediente <code class=\"bg-gray-200 px-1 rounded font-semibold\">{$numExp}</code> asociado a la constancia <code class=\"bg-gray-200 px-1 rounded font-semibold\">{$numConst}</code>.";
                }
                return 'Generó un expediente que fue eliminado posteriormente.';

            case 'EDICION_DATO_CRUDO':
                // Nota: Usamos 'optional()' para evitar errores si un usuario ha sido eliminado.
                $usuarioAfectadoNombre = $e(optional(User::find($this->metadata['afectado_user_id'] ?? null))->name ?? 'Desconocido');
                $valorAntiguo = $e($this->metadata['cambio']['valor_anterior'] ?? 'N/A');
                $valorNuevo = $e($this->metadata['cambio']['valor_nuevo'] ?? 'N/A');
                $nombreColumna = $e($this->nombre_columna_editada); // Reutilizamos el otro accesor. ¡Eficiente!

                $html = "<p><strong>Contexto:</strong> Se realizó una edición en los datos de <strong>{$usuarioAfectadoNombre}</strong>.</p>";
                $html .= "<p>En la columna <strong>\"{$nombreColumna}\"</strong>, se cambió de ";
                $html .= "<code class=\"bg-red-100 text-red-700 px-1 rounded\">'{$valorAntiguo}'</code> a ";
                $html .= "<code class=\"bg-green-100 text-green-700 px-1 rounded\">'{$valorNuevo}'</code>.</p>";
                return $html;
            
            case 'ELIMINACION_DATO_CRUDO':
                $usuarioAfectadoNombre = $e(optional(User::find($this->referencia_id))->name ?? 'Desconocido');
                
                $html = "<p><strong>Contexto:</strong> Se eliminó una fila de datos del usuario <strong>{$usuarioAfectadoNombre}</strong>.</p>";
                
                // Verificamos si tenemos el resumen de datos eliminados
                if (isset($this->metadata['datos_eliminados']) && is_array($this->metadata['datos_eliminados'])) {
                    $mes = $e($this->metadata['datos_eliminados']['MESES'] ?? 'N/A');
                    $anio = $e($this->metadata['datos_eliminados']['AÑO'] ?? 'N/A');
                    $liquido = $e($this->metadata['datos_eliminados']['LIQUIDO'] ?? 'N/A');
                    
                    $html .= "<div><p class=\"font-semibold\">Resumen de datos eliminados:</p>";
                    $html .= "<ul class=\"list-disc list-inside pl-2 text-xs\">";
                    $html .= "<li><strong>Mes:</strong> {$mes}</li>";
                    $html .= "<li><strong>Año:</strong> {$anio}</li>";
                    $html .= "<li><strong>Líquido:</strong> {$liquido}</li>";
                    $html .= "</ul></div>";
                }
                return $html;

            default:
                // Si la acción es desconocida o no tiene formato especial, mostramos el JSON crudo de forma segura.
                if ($this->metadata) {
                    return '<pre class="text-xs bg-gray-100 p-2 rounded">' . $e(json_encode($this->metadata, JSON_PRETTY_PRINT)) . '</pre>';
                }
                return '<span class="text-gray-500">No hay detalles adicionales para esta acción.</span>';
        }
    }
    // --- 🔥 FIN DEL NUEVO ACCESOR INTELIGENTE 🔥 ---
}   