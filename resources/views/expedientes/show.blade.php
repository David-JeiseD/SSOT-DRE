@extends('layouts.app')

@section('content')
<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Detalle del Expediente: <span class="text-indigo-600">{{ $expediente->numero_expediente }}</span></h1>
                <p class="text-gray-600">Visualización de los datos contenidos en el expediente.</p>
            </div>
            <div>
                <a href="{{ route('expedientes.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border rounded-lg hover:bg-gray-100">&larr; Volver a la Búsqueda</a>
                {{-- Botón de Descarga --}}
                <a href="{{ route('expedientes.descargar', $expediente) }}" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 shadow-lg ml-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Descargar Excel
                </a>
                <a href="{{ route('expedientes.pdf', $expediente) }}" class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 shadow-lg ml-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Descargar PDF
                </a>
            </div>
        </div>

        <!-- Tarjeta de Información General -->
        <div class="bg-white rounded-2xl shadow-lg border p-8 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-6">Información General</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm font-medium text-gray-500">Usuario</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $expediente->constancia->user->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Número de Constancia</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $expediente->constancia->numero_constancia }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Periodo de Datos</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $expediente->rango_fechas_descripcion }}</p>
                </div>
                 <div>
                    <p class="text-sm font-medium text-gray-500">Fecha de Generación</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $expediente->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Tabla de Datos del Expediente -->
        <div class="bg-white rounded-2xl shadow-lg border">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach($columnas as $columna)
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $columna->nombre_display }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tabla as $fila)
                            <tr>
                                @foreach($columnas as $columna)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $fila['datos'][$columna->id] ?? '' }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $columnas->count() }}" class="text-center py-12 text-gray-500">
                                    No se encontraron datos en este expediente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection