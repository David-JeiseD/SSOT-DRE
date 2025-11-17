@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                        <svg class="w-8 h-8 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Detalle del Usuario
                    </h1>
                    <p class="text-gray-600 mt-1">Información completa y historial de acciones de <span class="font-semibold">{{ $user->name }}</span></p>
                </div>
                <a href="{{ route('admin.users.index') }}" 
                   class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver a la lista
                </a>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Columna Izquierda: Información del Usuario (Sticky) -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden sticky top-8">
                    <div class="p-6 text-center bg-gray-50 border-b">
                        <div class="w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-4xl font-bold text-indigo-600">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
                        <p class="text-gray-500 mt-1">{{ $user->email }}</p>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">DNI</p>
                            <p class="text-lg font-medium text-gray-800">{{ $user->dni ?? 'No registrado' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Rol</p>
                            <p class="text-lg font-medium text-gray-800">
                                @if($user->roles->isNotEmpty())
                                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">{{ $user->roles->first()->name }}</span>
                                @else
                                    <span class="text-gray-500">Sin rol</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Miembro Desde</p>
                            <p class="text-lg font-medium text-gray-800">{{ $user->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    
                    @php
                        // Calculamos los contadores una sola vez para eficiencia
                        $generados = $user->acciones->where('tipo_accion', 'GENERACION_EXPEDIENTE')->count();
                        $editados = $user->acciones->where('tipo_accion', 'EDICION_DATO_CRUDO')->count();
                        $eliminados = $user->acciones->where('tipo_accion', 'ELIMINACION_DATO_CRUDO')->count();
                    @endphp

                    <div class="bg-gray-50 px-6 py-4 border-t grid grid-cols-3 text-center">
                        <div>
                            <p class="text-2xl font-bold text-blue-600">{{ $generados }}</p>
                            <p class="text-xs text-gray-500">Generados</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-yellow-600">{{ $editados }}</p>
                            <p class="text-xs text-gray-500">Editados</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600">{{ $eliminados }}</p>
                            <p class="text-xs text-gray-500">Eliminados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Historial de Acciones -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b">
                        <h3 class="text-xl font-bold text-gray-800">Historial de Acciones</h3>
                        <p class="text-gray-500 mt-1">{{ $user->acciones->count() }} acciones registradas en total</p>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @forelse($user->acciones as $accion)
                            <div class="p-6 hover:bg-gray-50">
                                @php
                                    // Definimos la apariencia de cada tipo de acción
                                    $infoAccion = match($accion->tipo_accion) {
                                        'GENERACION_EXPEDIENTE' => ['color' => 'blue', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>', 'label' => 'Generó Expediente'],
                                        'EDICION_DATO_CRUDO' => ['color' => 'yellow', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>', 'label' => 'Editó Dato'],
                                        'ELIMINACION_DATO_CRUDO' => ['color' => 'red', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>', 'label' => 'Eliminó Fila de Datos'],
                                        'DESCARGA_EXPEDIENTE' => ['color' => 'green', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>', 'label' => 'Descargó Expediente'],
                                        default => ['color' => 'gray', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>', 'label' => 'Acción Desconocida'],
                                    };
                                @endphp

                                <div class="flex items-start space-x-4">
                                    <div class="w-10 h-10 bg-{{$infoAccion['color']}}-100 text-{{$infoAccion['color']}}-600 rounded-full flex items-center justify-center flex-shrink-0">
                                        {!! $infoAccion['icon'] !!}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <p class="font-semibold text-gray-800">{{ $infoAccion['label'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $accion->created_at->diffForHumans() }}</p>
                                        </div>
                                        
                                        {{-- Mostramos los detalles guardados en la columna metadata --}}
                                        <div class="mt-2 text-sm text-gray-600 bg-gray-100 p-3 rounded-lg space-y-2">
    {!! $accion->descripcion_html !!}
</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center text-gray-500">
                                <p>Este usuario aún no ha realizado ninguna acción en el sistema.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection