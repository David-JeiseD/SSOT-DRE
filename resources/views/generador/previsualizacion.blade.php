@extends('layouts.app')
@push('head')
    {{-- Usamos el CSS por defecto para mejor integración con Tailwind --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    {{-- SweetAlert2 para los modales de confirmación --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
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
                <h2 class="text-xl font-semibold text-gray-700 border-b pb-3 mb-4">Paso 3: Previsualización para <span class="text-indigo-600">{{ $usuario->name }}</span></h2>
                
                <div class="overflow-x-auto border rounded-lg" style="max-height: 400px; overflow-y: auto;">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                @foreach($columnas as $columna)
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $columna->nombre_display }}</th>
                                @endforeach
                                {{-- 🔥 NUEVA COLUMNA DE ACCIONES (SOLO PARA ADMINS) --}}
                                @role('admin')
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                @endrole
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($tabla as $idFilaOrigen => $fila)
                                {{-- 🔥 Añadimos un ID a cada fila para poder manipularla con JS --}}
                                <tr id="fila-{{ $idFilaOrigen }}">
                                    @foreach($columnas as $columna)
                                        {{-- 🔥 Añadimos un data-attribute a cada celda para la edición --}}
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" data-columna-id="{{ $columna->id }}">
                                            {{ $fila['datos'][$columna->id] ?? '' }}
                                        </td>
                                    @endforeach
                                    {{-- 🔥 NUEVOS BOTONES DE ACCIÓN (SOLO PARA ADMINS) --}}
                                    @role('admin')
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            {{-- 🔥 Añadimos data-attributes para pasar el ID de la fila a JS --}}
                                            <button type="button" class="text-indigo-600 hover:text-indigo-900 btn-editar" data-fila-id="{{ $idFilaOrigen }}">Editar</button>
                                            <button type="button" class="text-red-600 hover:text-red-900 ml-4 btn-eliminar" data-fila-id="{{ $idFilaOrigen }}">Eliminar</button>
                                        </td>
                                    @endrole
                                </tr>
                            @empty
                                <tr>
                                    {{-- 🔥 Ajustamos el colspan para incluir la nueva columna de acciones --}}
                                    <td colspan="{{ $columnas->count() + (auth()->user()->hasRole('admin') ? 1 : 0) }}" class="text-center py-8 text-gray-500">
                                        No se encontraron datos para los criterios seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FORMULARIO DE CONFIRMACIÓN FINAL -->
            <div class="mt-10 pt-8 border-t">
                <h2 class="text-xl font-semibold text-gray-700 mb-6">Paso 4: Asignar Identificadores y Generar</h2>
                <form action="{{ route('generador.generarFinal') }}" method="POST" id="form-generador">
                    @csrf
                    {{-- Pasamos los datos de la consulta anterior de forma oculta --}}
                    <input type="hidden" name="user_id" value="{{ $requestData['user_id'] }}">
                    <input type="hidden" name="fecha_desde" value="{{ \Carbon\Carbon::parse($requestData['fecha_desde'])->format('Y-m-d') }}">
                    <input type="hidden" name="fecha_hasta" value="{{ \Carbon\Carbon::parse($requestData['fecha_hasta'])->format('Y-m-d') }}">
                    @foreach($requestData['columnas'] as $columnaId)
                        <input type="hidden" name="columnas[]" value="{{ $columnaId }}">
                    @endforeach

                    <div class="grid md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-lg border">
                        {{-- Campo Tipo de Documento --}}
                        <div>
                            <label for="tipo_documento_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento *</label>
                            <select name="tipo_documento_id" id="tipo_documento_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                                <option value="">Seleccione...</option>
                                @foreach($tiposDeDocumento as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                            <span class="text-red-500 text-sm hidden" id="error-tipo-documento">Este campo es obligatorio.</span>
                        </div>
                        
                        {{-- BUSCADOR DE CONSTANCIAS MEJORADO --}}
                        <div>
                            <label for="constancia_search" class="block text-sm font-medium text-gray-700 mb-1">Número de Constancia (Selecciona o Crea una nueva) *</label>
                            <select id="constancia_search" name="numero_constancia" required placeholder="Escribe para buscar o crear..."></select>
                            <span class="text-red-500 text-sm hidden" id="error-constancia">Este campo es obligatorio.</span>
                        </div>

                        {{-- Campo Número de Expediente --}}
                        <div>
                            <label for="numero_expediente" class="block text-sm font-medium text-gray-700 mb-1">Número de Nuevo Expediente *</label>
                            <input type="text" name="numero_expediente" id="numero_expediente" required class="w-full border-gray-300 rounded-lg shadow-sm" placeholder="Ej: EXP-2025-001">
                            <span class="text-red-500 text-sm hidden" id="error-expediente">Este campo es obligatorio.</span>
                        </div>
                    </div>
                    <div class="pt-8 text-right">
                        <button type="submit" class="inline-flex items-center px-8 py-3 bg-green-600 text-white font-medium rounded-lg shadow-sm hover:bg-green-700" id="submit-button">
                            Confirmar y Descargar Excel
                        </button>
                    </div>
                </form>
            </div>

           
        </div>
    </div>
</div>
@endsection
@push('scripts')
    {{-- Incluimos el JS de Tom Select --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#constancia_search', {
            valueField: 'numero_constancia',
            labelField: 'numero_constancia',
            searchField: ['numero_constancia'],
            
            create: true, // Permite crear nuevas constancias

            // 🔥 RENDERIZADO PERSONALIZADO PARA UNA MEJOR UI 🔥
            render: {
                option: function(item, escape) {
                    // Si el 'id' no existe, es una nueva opción que el usuario está creando
                    const isNew = typeof item.id === 'undefined';
                    const newTag = isNew ? '<span class="text-xs text-green-500 font-semibold ml-2">[NUEVO]</span>' : '';
                    
                    return `<div>
                                <span class="font-medium">${escape(item.numero_constancia)}</span>
                                ${newTag}
                            </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.numero_constancia)}</div>`;
                },
                no_results:function(data,escape){
                    return '<div class="p-2">No se encontraron constancias. Escribe el número completo y presiona Enter para crearla.</div>';
                },
            },
            
            // Lógica AJAX
            load: function(query, callback) {
                if (!query.length) return callback();
                const url = `{{ route('api.constancias.buscar') }}?q=${encodeURIComponent(query)}`;
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
    {{-- 🔥 NUEVO SCRIPT PARA LAS ACCIONES DE EDICIÓN Y ELIMINACIÓN --}}
    @role('admin')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabla = document.querySelector('table'); // Escuchamos en la tabla entera

    // Un único "guardia" para todos los clics dentro de la tabla
    tabla.addEventListener('click', function(event) {
        const target = event.target;

        // --- MANEJO DE LA ELIMINACIÓN ---
        if (target.classList.contains('btn-eliminar')) {
            confirmarEliminacion(target);
            return; // Detenemos la ejecución aquí
        }

        // --- MANEJO DE LA EDICIÓN ---
        if (target.classList.contains('btn-editar')) {
            modoEdicion(target);
            return;
        }

        // --- MANEJO DEL GUARDADO ---
        if (target.classList.contains('btn-guardar')) {
            guardarCambios(target);
            return;
        }
    });

    function confirmarEliminacion(boton) {
        const filaId = boton.dataset.filaId;
        const url = `/admin/datos-crudos/${filaId}`;

        Swal.fire({
            title: '¿Estás absolutamente seguro?',
            text: "Esta acción eliminará todos los datos de esta fila. ¡No se puede deshacer!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡elimínalo!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    document.getElementById(`fila-${filaId}`).remove();
                    Swal.fire('¡Eliminado!', data.message, 'success');
                })
                .catch(error => Swal.fire('Error', 'No se pudo eliminar la fila.', 'error'));
            }
        });
    }

    function modoEdicion(botonEditar) {
        const filaId = botonEditar.dataset.filaId;
        const fila = document.getElementById(`fila-${filaId}`);
        
        // Verificamos si ya hay otra fila en modo edición
        const otraFilaEditando = document.querySelector('tr.fila-en-edicion');
        if (otraFilaEditando) {
            Swal.fire('Atención', 'Por favor, guarda o cancela la edición de la otra fila antes de editar una nueva.', 'warning');
            return;
        }

        fila.classList.add('fila-en-edicion');
        document.getElementById('submit-button').disabled = true;

        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const valorActual = td.textContent.trim();
            td.innerHTML = `<input type="text" class="w-full border-gray-300 rounded-md shadow-sm" value="${valorActual}">`;
        });

        // Cambiamos el botón "Editar" a "Guardar"
        botonEditar.textContent = 'Guardar';
        botonEditar.classList.remove('btn-editar', 'text-indigo-600');
        botonEditar.classList.add('btn-guardar', 'text-green-600');
        
        // Ocultamos el botón "Eliminar" y mostramos "Cancelar"
        const botonEliminar = fila.querySelector('.btn-eliminar');
        botonEliminar.style.display = 'none';
        // (Opcional: podrías añadir un botón "Cancelar" aquí)
    }

    function guardarCambios(botonGuardar) {
        const filaId = botonGuardar.dataset.filaId;
        const fila = document.getElementById(`fila-${filaId}`);
        const url = `/admin/datos-crudos/${filaId}`; // La URL del endpoint PUT

        // 1. Recolectamos los nuevos datos de los inputs
        const nuevosDatos = {};
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const columnaId = td.dataset.columnaId;
            nuevosDatos[columnaId] = td.querySelector('input').value;
        });

        // Mostramos un spinner de carga mientras se procesa
        botonGuardar.disabled = true;
        botonGuardar.innerHTML = 'Guardando...';

        // 2. Enviamos la petición AJAX
        fetch(url, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ datos: nuevosDatos })
        })
        .then(response => {
            // Si la respuesta no es OK (ej. error 500, 422), lanzamos un error
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json(); // Si es OK, procesamos el JSON
        })
        .then(data => {
            // 3. Si tiene éxito, revertimos la UI con los datos actualizados
            revertirFilaAEstadoNormal(fila, data.datos);
            Swal.fire('¡Guardado!', data.message, 'success');
        })
        .catch(error => {
            // 4. Si falla, mostramos el error
            console.error('Error al guardar:', error);
            // Mostramos los errores de validación si existen
            const errorMessage = error.errors ? Object.values(error.errors).join('\n') : 'No se pudieron guardar los cambios.';
            Swal.fire('Error', errorMessage, 'error');
            // Re-habilitamos el botón para que el usuario pueda intentar de nuevo
            botonGuardar.disabled = false;
            botonGuardar.innerHTML = 'Guardar';
        });
    }

    // 🔥 ========================================================== 🔥
    // 🔥 NUEVA FUNCIÓN PARA REVERTIR LA VISTA
    // 🔥 ========================================================== 🔥
    function revertirFilaAEstadoNormal(fila, datosActualizados) {
        // Revertimos los inputs a texto plano, usando los datos que nos devuelve el servidor
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const columnaId = td.dataset.columnaId;
            td.textContent = datosActualizados[columnaId] ?? '';
        });

        // Revertimos el botón "Guardar" a "Editar"
        const boton = fila.querySelector('.btn-guardar');
        boton.textContent = 'Editar';
        boton.innerHTML = 'Editar'; // Aseguramos que se limpie el spinner
        boton.disabled = false;
        boton.classList.remove('btn-guardar', 'text-green-600');
        boton.classList.add('btn-editar', 'text-indigo-600');
        
        // Mostramos de nuevo el botón "Eliminar"
        const botonEliminar = fila.querySelector('.btn-eliminar');
        if (botonEliminar) botonEliminar.style.display = 'inline';

        // Quitamos la clase de estado de edición
        fila.classList.remove('fila-en-edicion');
        
        // Habilitamos el botón principal del formulario si no hay otras filas editándose
        if (!document.querySelector('tr.fila-en-edicion')) {
            document.getElementById('submit-button').disabled = false;
        }
    }
});
</script>
@endrole
@endpush