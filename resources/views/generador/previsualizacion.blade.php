@extends('layouts.app')

@section('content')
<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Generador de Documentos</h1>
            {{-- Podríamos añadir un botón para volver al paso anterior si fuera necesario --}}
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
            <!-- SECCIÓN DE PREVISUALIZACIÓN -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-700 border-b pb-3 mb-4">Paso 3: Previsualización del Reporte para <span class="text-indigo-600">{{ $usuario->name }}</span></h2>
                
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                {{-- Generamos los encabezados de la tabla dinámicamente --}}
                                @foreach($columnas as $columna)
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $columna->nombre_display }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($tabla as $claveFila => $fila)
                                <tr>
                                    {{-- Para cada columna que el usuario seleccionó... --}}
                                    @foreach($columnas as $columna)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {{-- ...buscamos su valor en los datos de la fila --}}
                                            {{ $fila['datos'][$columna->id] ?? '' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $columnas->count() }}" class="text-center py-8 text-gray-500">
                                        No se encontraron datos para los criterios seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FORMULARIO DE CONFIRMACIÓN FINAL -->
            <form action="{{ route('generador.generarFinal') }}" method="POST">
                @csrf
                {{-- Pasamos los datos de la consulta anterior de forma oculta --}}
                <input type="hidden" name="user_id" value="{{ $requestData['user_id'] }}">
                <input type="hidden" name="fecha_desde" value="{{ $requestData['fecha_desde'] }}">
                <input type="hidden" name="fecha_hasta" value="{{ $requestData['fecha_hasta'] }}">
                @foreach($requestData['columnas'] as $colId)
                    <input type="hidden" name="columnas[]" value="{{ $colId }}">
                @endforeach

                <div class="space-y-6 pt-8 border-t">
                    <h2 class="text-xl font-semibold text-gray-700">Paso 4: Asignar Identificadores y Generar</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="numero_constancia" class="block text-sm font-medium text-gray-700 mb-1">Número de Nueva Constancia *</label>
                            <input type="text" name="numero_constancia" id="numero_constancia" required class="w-full border-gray-300 rounded-lg" placeholder="Ej: C-2025-001">
                        </div>
                        <div>
                            <label for="numero_expediente" class="block text-sm font-medium text-gray-700 mb-1">Número de Nuevo Expediente *</label>
                            <input type="text" name="numero_expediente" id="numero_expediente" required class="w-full border-gray-300 rounded-lg" placeholder="Ej: EXP-2025-001">
                        </div>
                    </div>
                    <div class="pt-6 text-right">
                        <button type="submit" class="inline-flex items-center px-8 py-3 bg-green-600 text-white font-medium rounded-lg">
                            Confirmar y Descargar Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection