<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DniController extends Controller
{
    /**
     * Obtener datos del DNI desde la API de Perú
     */
    public function obtenerDatos(Request $request)
    {
        $request->validate([
            'dni' => 'required|digits:8',
        ]);

        try {
            $token = env('API_PERU_TOKEN');
            
            if (!$token) {
                \Log::error('API_PERU_TOKEN no está configurado');
                return response()->json([
                    'success' => false,
                    'message' => 'Token de API no configurado',
                ], 500);
            }

            \Log::info('Consultando DNI: ' . $request->dni);

            // Usar POST como indica la documentación
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->post('https://apiperu.dev/api/dni', [
                'dni' => $request->dni,
            ]);

            \Log::info('Respuesta API Status: ' . $response->status());
            \Log::info('Respuesta API: ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                
                // Verificar si la respuesta contiene los datos esperados
                if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    $nombreCompleto = $data['data']['nombre_completo'] ?? '';
                    
                    \Log::info('DNI encontrado: ' . $nombreCompleto);
                    
                    return response()->json([
                        'success' => true,
                        'nombre_completo' => $nombreCompleto,
                        'nombres' => $data['data']['nombres'] ?? '',
                        'apellido_paterno' => $data['data']['apellido_paterno'] ?? '',
                        'apellido_materno' => $data['data']['apellido_materno'] ?? '',
                    ]);
                }
                
                \Log::warning('DNI no encontrado en la base de datos: ' . $request->dni);
                
                return response()->json([
                    'success' => false,
                    'message' => 'DNI no encontrado en la base de datos',
                ], 404);
            }

            $errorMessage = 'Error al consultar la API';
            if ($response->json() && isset($response->json()['message'])) {
                $errorMessage = $response->json()['message'];
            }
            
            \Log::error('Error en API: ' . $errorMessage . ' - Status: ' . $response->status());

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], $response->status());

        } catch (\Exception $e) {
            \Log::error('Error en DniController: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
