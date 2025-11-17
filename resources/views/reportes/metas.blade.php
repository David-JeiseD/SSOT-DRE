@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 py-8">
    <div class="container mx-auto px-4">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                <svg class="w-10 h-10 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Reporte de Metas por Encargado
            </h1>
            <p class="text-gray-600 mt-2">Seleccione un usuario y un periodo para evaluar su generación de expedientes.</p>
        </div>

        <!-- Formulario de Búsqueda (Sin cambios, ya estaba bien) -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 mb-8">
            <form action="{{ route('admin.reportes.metas.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                <div class="col-span-1 md:col-span-2">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Encargado</label>
                    <select name="user_id" id="user_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition">
                        <option value="">-- Seleccionar un usuario --</option>
                        @foreach($encargados as $encargado)
                            <option value="{{ $encargado->id }}" {{ (isset($inputs['user_id']) && $inputs['user_id'] == $encargado->id) ? 'selected' : '' }}>
                                {{ $encargado->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select name="year" id="year" required class="w-full border-gray-300 rounded-lg shadow-sm">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ (isset($inputs['year']) && $inputs['year'] == $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="month" id="month" required class="w-full border-gray-300 rounded-lg shadow-sm">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (isset($inputs['month']) && $inputs['month'] == $m) ? 'selected' : '' }}>
                                {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-span-1 md:col-span-1">
                     <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:from-indigo-600 hover:to-purple-700 transition duration-300 transform hover:scale-105 shadow-md">
                        Generar Reporte
                    </button>
                </div>
            </form>
        </div>

        {{-- Sección de Resultados --}}
        @if($resultados)
            <div class="space-y-8 animate-fade-in">
                <!-- Header de Resultados y Mensaje de Meta -->
                <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">
                        Resultados para: <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-purple-600">{{ $usuarioSeleccionado->name }}</span>
                    </h2>

                    {{-- 🔥 CAMBIO 1: Lógica de estilo ahora es dinámica y se basa en la meta real --}}
                    @php
                        // Aseguramos que el controlador pase $metaDelMes a la vista
                        $metaCumplida = ($metaDelMes > 0 && $resultados['totalGeneral'] >= $metaDelMes);
                    @endphp
                    <div class="p-4 rounded-lg flex items-center {{ $metaCumplida ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                         <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($metaCumplida)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            @endif
                        </svg>
                        <p class="font-semibold">{{ $mensajeMeta }}</p>
                    </div>
                </div>

                <!-- 🔥 CAMBIO 2: Tarjetas de Resumen completamente nuevas y enfocadas en el KPI correcto -->
                @php
                    $progreso = ($metaDelMes > 0) ? round(($resultados['totalGeneral'] / $metaDelMes) * 100) : 0;
                    // Limitar el progreso al 100% para la barra visual
                    $progresoBarra = min($progreso, 100);
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl text-center shadow-md border border-gray-200">
                        <p class="text-sm text-gray-500 uppercase font-bold tracking-wider">Meta del Mes</p>
                        <p class="text-5xl font-bold text-gray-800 mt-2">{{ $metaDelMes }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl text-center shadow-md border border-gray-200">
                        <p class="text-sm text-gray-500 uppercase font-bold tracking-wider">Expedientes Generados</p>
                        <p class="text-5xl font-bold text-indigo-600 mt-2">{{ $resultados['totalGeneral'] }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                        <div class="flex justify-between items-center mb-1">
                            <p class="text-sm text-gray-500 uppercase font-bold tracking-wider">Progreso</p>
                            <p class="text-lg font-bold text-indigo-600">{{ $progreso }}%</p>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 mt-2">
                            <div class="bg-gradient-to-r from-green-400 to-blue-500 h-4 rounded-full" style="width: {{ $progresoBarra }}%"></div>
                        </div>
                    </div>
                </div>


                <!-- 🔥 CAMBIO 3: Historial Detallado adaptado a los nuevos tipos de acción -->
                <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-700 mb-6">Log de Actividad del Periodo</h3>
                    <div class="space-y-4">
                    @forelse($resultados['detalles'] as $accion)
    <div class="bg-slate-50 p-4 rounded-lg border border-gray-200">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div class="flex-grow flex items-center gap-4">
                <span class="text-xs font-semibold uppercase px-3 py-1 rounded-full
                    @if($accion->tipo_accion == 'GENERACION_EXPEDIENTE') bg-green-100 text-green-800
                    @elseif($accion->tipo_accion == 'EDICION_DATO_CRUDO') bg-yellow-100 text-yellow-800
                    @elseif($accion->tipo_accion == 'ELIMINACION_DATO_CRUDO') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800
                    @endif
                ">
                    {{ str_replace('_', ' ', $accion->tipo_accion) }}
                </span>
                <p class="text-sm text-gray-500">{{ $accion->created_at->format('d/m/Y H:i A') }}</p>
            </div>
        </div>
        <!-- 🔥 Usamos el nuevo accesor aquí, dentro de un div para darle formato 🔥 -->
        <div class="w-full mt-2">
            <div class="text-sm text-gray-700 bg-slate-100 p-3 rounded-lg border">
                {!! $accion->descripcion_html !!}
            </div>
        </div>
    </div>
@empty
                             <div class="text-center py-10">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                <p class="text-center text-gray-500 py-4">No se encontraron acciones registradas en el periodo seleccionado.</p>
                            </div>
                        @endforelse

                        {{-- Paginación --}}
                        <div class="mt-6">
                            {{ $resultados['detalles']->appends($inputs)->links() }}
                        </div>
                    </div>
                </div>
            </div>
        @elseif(count($inputs) > 0)
             <div class="text-center py-10 bg-white rounded-2xl shadow-lg border border-gray-200">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <h3 class="text-xl font-semibold text-gray-700">Búsqueda sin resultados</h3>
                <p class="text-center text-gray-500 py-4">No se encontraron expedientes generados para este usuario en el periodo seleccionado.</p>
            </div>
        @endif

    </div>
</div>
<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }
</style>
@endsection