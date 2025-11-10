@extends('layouts.app')

@section('content')

@php
    $prioritarios = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion', 'reint_', 'neto_a_pagar'];
@endphp

<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Generador de Documentos</h1>
            <a href="{{ route('generador.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver a buscar usuario</a>
        </div>
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
            <div class="mb-8 p-6 bg-slate-50 rounded-lg border">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Resumen de Datos para: <span class="text-indigo-600">{{ $usuario->name }}</span></h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-600">Años con registros:</h3>
                        <p class="text-gray-800">{{ $aniosDisponibles->implode(', ') ?: 'Sin registros' }}</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-600">Total de columnas con datos:</h3>
                        <p class="text-gray-800">{{ $columnasDisponibles->count() }}</p>
                    </div>
                </div>
            </div>
            <form action="{{ route('generador.previsualizar') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ $usuario->id }}">
                <div class="space-y-8">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-700 border-b pb-3">Paso 2: Definir Periodo y Columnas</h2>
                        <div class="grid md:grid-cols-3 gap-6 mt-4">
                             <div>
                                <label for="fecha_desde" class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                                <input type="date" name="fecha_desde" id="fecha_desde" required class="w-full border-gray-300 rounded-lg">
                            </div>
                             <div>
                                <label for="fecha_hasta" class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" required class="w-full border-gray-300 rounded-lg">
                            </div>
                        </div>
                    </div>
                    <div>
                    {{-- SECCIÓN DATOS PRIORITARIOS --}}
<h3 class="text-lg font-semibold text-gray-600 mt-6 mb-3">Datos Prioritarios (Siempre Incluidos)</h3>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 border rounded-lg bg-gray-50">
    @foreach($columnasDisponibles as $columna)
        @if(in_array($columna->nombre_normalizado, $prioritarios))
            <div class="flex items-center">
                {{-- Marcados y deshabilitados para que no se puedan quitar --}}
                <input type="checkbox" name="columnas[]" id="columna_{{ $columna->id }}" value="{{ $columna->id }}" 
                       class="h-4 w-4 text-indigo-600 border-gray-300 rounded" 
                       checked disabled>
                {{-- Un input oculto para asegurar que su valor SÍ se envíe --}}
                <input type="hidden" name="columnas[]" value="{{ $columna->id }}">
                <label for="columna_{{ $columna->id }}" class="ml-2 block text-sm text-gray-700 font-medium">
                    {{ $columna->nombre_display }}
                </label>
            </div>
        @endif
    @endforeach
</div>

{{-- SECCIÓN DATOS OPCIONALES --}}
<h3 class="text-lg font-semibold text-gray-600 mt-6 mb-3">Datos Opcionales</h3>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 border rounded-lg">
    @foreach($columnasDisponibles as $columna)
        @if(!in_array($columna->nombre_normalizado, $prioritarios))
            <div class="flex items-center">
                <input type="checkbox" name="columnas[]" id="columna_{{ $columna->id }}" value="{{ $columna->id }}" 
                       class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                <label for="columna_{{ $columna->id }}" class="ml-2 block text-sm text-gray-700">
                    {{ $columna->nombre_display }}
                </label>
            </div>
        @endif
    @endforeach
</div>
                    </div>
                    <div class="pt-6 text-right">
                        <button type="submit" class="inline-flex items-center px-8 py-3 bg-indigo-600 text-white font-medium rounded-lg">
                            Generar Previsualización &rarr;
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection