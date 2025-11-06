@extends('layouts.app')

@push('head')
    {{-- Incluimos el CSS de Tom Select desde un CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
@endpush

@section('content')
<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Generador de Documentos</h1>
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200 max-w-2xl mx-auto">
            <form action="{{ route('generador.buscar') }}" method="POST">
                @csrf
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Paso 1: Seleccionar Usuario</h2>
                <div>
                    <label for="user_search" class="block text-sm font-medium text-gray-700 mb-1">Buscar Usuario por DNI o Nombre</label>
                    
                    {{-- ESTE ES EL NUEVO CAMPO DE BÚSQUEDA --}}
                    <select id="user_search" name="user_id" required placeholder="Escribe para buscar..."></select>
                    
                </div>
                <div class="pt-6 text-right">
                    <button type="submit" class="inline-flex items-center px-8 py-3 bg-indigo-600 text-white font-medium rounded-lg">
                        Buscar Datos Disponibles &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- Incluimos el JS de Tom Select --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect('#user_search', {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'dni'],
                
                // Opción para renderizar cómo se ven los resultados
                render: {
                    option: function(item, escape) {
                        return `<div>
                                    <span class="font-semibold">${escape(item.name)}</span>
                                    <span class="block text-sm text-gray-500">DNI: ${escape(item.dni)}</span>
                                </div>`;
                    },
                    item: function(item, escape) {
                        return `<div title="${escape(item.email)}">${escape(item.name)}</div>`;
                    }
                },
                
                // La magia del AJAX
                load: function(query, callback) {
                    if (!query.length) return callback();
                    
                    const url = `{{ route('api.usuarios.buscar') }}?q=${encodeURIComponent(query)}`;
                    
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json);
                        }).catch(()=>{
                            callback();
                        });
                },
                
                // Mensaje cuando no hay resultados
                loadThrottle: 300, // Espera 300ms después de que el usuario deja de teclear
                placeholder: 'Escribe el nombre o DNI del usuario...'
            });
        });
    </script>
@endpush