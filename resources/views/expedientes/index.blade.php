@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Búsqueda de Expedientes</h1>
            <p class="text-gray-600 mt-2">Encuentra constancias y descarga los expedientes generados.</p>
        </div>

        <!-- Formulario de Búsqueda -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 mb-8">
            <form action="{{ route('expedientes.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div>
                    <label for="numero_constancia" class="block text-sm font-medium text-gray-700">N° de Constancia</label>
                    <input type="text" name="numero_constancia" id="numero_constancia" value="{{ $inputs['numero_constancia'] ?? '' }}" class="mt-1 w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="user_search" class="block text-sm font-medium text-gray-700">Usuario (Buscar por Nombre o DNI)</label>
                    {{-- Dejamos un select, pero TomSelect lo convertirá en un campo de búsqueda. 
                        Le cambiamos el 'name' y 'id' para que el script lo encuentre. --}}
                    <select name="user_id" id="user_search" placeholder="Escribe para buscar..."></select>
                </div>
                <div>
                    <label for="tipo_documento_id" class="block text-sm font-medium text-gray-700">Tipo Documento</label>
                     <select name="tipo_documento_id" id="tipo_documento_id" class="mt-1 w-full border-gray-300 rounded-lg">
                        <option value="">-- Todos --</option>
                        @foreach($tiposDocumento as $tipo)
                             <option value="{{ $tipo->id }}" @selected(isset($inputs['tipo_documento_id']) && $inputs['tipo_documento_id'] == $tipo->id)>{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg">Buscar</button>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div class="space-y-6">
            @forelse($constancias as $constancia)
                <div class="bg-white rounded-2xl shadow-lg border">
                    <!-- Cabecera de la Constancia -->
                    <div class="p-6 border-b bg-gray-50">
                        <h2 class="text-xl font-bold text-gray-800">Constancia N°: {{ $constancia->numero_constancia }}</h2>
                        <p class="text-sm text-gray-600">
                            Para: <span class="font-semibold">{{ $constancia->user->name }}</span> | 
                            Tipo: <span class="font-semibold">{{ $constancia->tipoDocumento->nombre }}</span>
                        </p>
                    </div>
                    <!-- Lista de Expedientes -->
                    <div class="divide-y">
                        @forelse($constancia->expedientes as $expediente)
                            <div class="p-4 flex justify-between items-center hover:bg-slate-50">
                                <div>
                                    <p class="font-semibold text-gray-700">Expediente N°: {{ $expediente->numero_expediente }}</p>
                                    <p class="text-xs text-gray-500">
                                        Generado por: {{ $expediente->generadoPor->name }} el {{ $expediente->created_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                <a href="{{ route('expedientes.show', $expediente) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                    Ver Detalle
                                </a>
                            </div>
                        @empty
                             <p class="p-4 text-center text-gray-500">Esta constancia no tiene expedientes generados.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 py-12">No se encontraron constancias con los criterios de búsqueda.</p>
            @endforelse
        </div>
        
        <!-- Paginación -->
        <div class="mt-8">
            {{ $constancias->appends($inputs)->links() }}
        </div>
    </div>
</div>

@endsection {{-- Cierre de la sección content --}}
{{-- 🔥 AÑADIMOS ESTA NUEVA SECCIÓN AL FINAL 🔥 --}}
@push('scripts')
<script>
// Esperamos a que todo el HTML esté cargado
document.addEventListener('DOMContentLoaded', function () {

new TomSelect('#user_search', {
valueField: 'id',
labelField: 'name',
searchField: ['name', 'dni'],

// Esta es la parte mágica que se conecta a tu API
load: function(query, callback) {
if (!query.length) return callback();

// Usamos la ruta que ya tienes en Laravel
const url = `{{ route('api.usuarios.buscar') }}?q=${encodeURIComponent(query)}`;

fetch(url)
.then(response => response.json())
.then(json => {
callback(json); // Pasamos los resultados a TomSelect
}).catch(()=>{
callback();
});
},

// Opcional: Hacemos que los resultados se vean más bonitos
render: {
option: function(item, escape) {
return `<div>
<span class="font-semibold">${escape(item.name)}</span>
<span class="block text-sm text-gray-500">${escape(item.dni)}</span>
</div>`;
},
item: function(item, escape) {
return `<div>${escape(item.name)}</div>`;
}
},

// Si venimos de una búsqueda anterior, precargamos el valor
@if(isset($inputs['user_id']) && $usuario = \App\Models\User::find($inputs['user_id']))
options: [@json($usuario)],
items: [@json($usuario->id)]
@endif
});
});
</script>
@endpush