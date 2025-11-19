@extends('layouts.app')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
@endpush

@section('content')
<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-800">Gestión de Datos Crudos</h1>
            <p class="text-gray-600 mt-2">Selecciona un usuario para ver, editar o añadir sus registros de pago.</p>
        </div>

        <!-- Card de Búsqueda -->
        <div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow-lg border">
            <div class="space-y-2">
                <label for="user_search" class="block text-lg font-medium text-gray-800">Buscar Usuario por Nombre o DNI</label>
                <p class="text-sm text-gray-500 mb-4">Comienza a escribir para ver los resultados.</p>
                <select id="user_search" name="user_id" required placeholder="Escribe para buscar..."></select>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#user_search', {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'dni'],
            
            // Renderizado personalizado para mostrar más información
            render: {
                option: function(item, escape) {
                    return `<div class="flex items-center">
                                <div>
                                    <div class="font-semibold">${escape(item.name)}</div>
                                    <div class="text-sm text-gray-500">DNI: ${escape(item.dni) || 'N/A'}</div>
                                </div>
                            </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            },
            
            // Al seleccionar un usuario, redirigimos a su página de gestión
            onChange: (value) => {
                if (value) {
                    const url = "{{ route('admin.gestion-datos.show', ':id') }}".replace(':id', value);
                    window.location.href = url;
                }
            },

            // Lógica AJAX para buscar usuarios
            load: function(query, callback) {
                if (!query.length || query.length < 3) return callback();
                const url = `{{ route('api.usuarios.buscar') }}?q=${encodeURIComponent(query)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(()=>{
                        callback();
                    });
            },
            loadThrottle: 300,
        });
    });
</script>
@endpush