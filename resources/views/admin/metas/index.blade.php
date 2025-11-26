@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-6">Establecer Metas Mensuales</h1>

        <!-- Formulario para establecer metas -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 mb-8">
             <form action="{{ route('admin.metas.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                @csrf
                <div class="col-span-1 md:col-span-2">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Encargado</label>
                    <select name="user_id" id="user_id" required class="w-full border-gray-300 rounded-lg shadow-sm">
                        @foreach($encargados as $encargado)
                            <option value="{{ $encargado->id }}">{{ $encargado->name }}</option>
                        @endforeach
                    </select>
                </div>
                 <div>
                    <label for="anio" class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select name="anio" id="anio" required class="w-full border-gray-300 rounded-lg shadow-sm">
                        @for ($y = date('Y') + 1; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="mes" class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="mes" id="mes" required class="w-full border-gray-300 rounded-lg shadow-sm">
                         @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                                {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    {{-- 🔥 CAMBIO 1: Etiqueta actualizada para mayor claridad --}}
                    <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Meta (N° Expedientes)</label>
                    <input type="number" name="cantidad" id="cantidad" required class="w-full border-gray-300 rounded-lg shadow-sm" min="0" placeholder="Ej: 100">
                </div>
                 <div class="col-span-1 md:col-span-5">
                    <button type="submit" class="w-full md:w-auto bg-indigo-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-indigo-700 transition">Guardar Meta</button>
                </div>
            </form>
        </div>

        <!-- Historial de Metas Establecidas -->
        <h2 class="text-2xl font-bold text-gray-700 mb-4">Historial de Metas</h2>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="divide-y divide-gray-200">
                @forelse($metasPorUsuario as $userId => $metas)
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-indigo-700">{{ $metas->first()->user->name }}</h3>
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach($metas as $meta)
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-center">
                                    <p class="font-bold text-gray-800">{{ Carbon\Carbon::create()->month($meta->mes)->translatedFormat('F') }} {{ $meta->anio }}</p>
                                    <p class="text-2xl font-bold text-indigo-600">{{ $meta->cantidad }}</p>
                                    
                                    <p class="text-xs text-gray-500">expedientes</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-center text-gray-500">Aún no se han establecido metas para ningún usuario.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection