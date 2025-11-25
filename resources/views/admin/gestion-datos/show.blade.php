@extends('layouts.app')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- CSS para TomSelect --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
@endpush

@section('content')
<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <!-- Header y Botones de Acción -->
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Gestión de Datos para: <span class="text-indigo-600">{{ $usuario->name }}</span></h1>
                <p class="text-gray-600">Edita, elimina o añade nuevos registros de pago.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.gestion-datos.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border rounded-lg hover:bg-gray-100 transition">&larr; Volver</a>
                <button id="btn-anadir-fila" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Añadir Registro
                </button>
            </div>
        </div>

        @include('partials.alerts')

        <!-- Selector de columnas para mostrar/ocultar -->
        <div class="mb-6 bg-white p-5 rounded-lg shadow-sm border border-gray-200">
            <label for="filtro-columnas" class="block text-sm font-semibold text-gray-700 mb-3">📊 Mostrar/Ocultar Columnas Opcionales</label>
            <select id="filtro-columnas" multiple placeholder="Selecciona columnas para mostrar...">
                @foreach($columnas as $columna)
                    @if(!$columna->es_fijo)
                        <option value="{{ $columna->id }}" selected>{{ $columna->nombre_display }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <!-- Tabla de Datos -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto max-h-[48rem]">
                <table class="min-w-full divide-y divide-gray-200" id="tabla-gestion-datos">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 sticky top-0 z-10">
                        <tr>
                            @foreach($columnas as $columna)
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider" data-columna-id="{{ $columna->id }}">{{ $columna->nombre_display }}</th>
                            @endforeach
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="tabla-cuerpo">
                        @forelse($tabla as $idFilaOrigen => $fila)
                            <tr id="fila-{{ $idFilaOrigen }}" class="hover:bg-gray-50 transition">
                                @foreach($columnas as $columna)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" data-columna-id="{{ $columna->id }}">
                                        {{ $fila['datos'][$columna->id] ?? '' }}
                                    </td>
                                @endforeach
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
                                    <button type="button" class="text-indigo-600 hover:text-indigo-900 font-semibold transition btn-editar" data-fila-id="{{ $idFilaOrigen }}">Editar</button>
                                    <button type="button" class="text-red-600 hover:text-red-900 font-semibold transition btn-eliminar" data-fila-id="{{ $idFilaOrigen }}">Eliminar</button>
                                </td>
                            </tr>
                        @empty
                            <tr id="fila-vacia-placeholder">
                                <td colspan="{{ $columnas->count() + 1 }}" class="text-center py-12 text-gray-500">
                                    Este usuario no tiene datos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Template (sin cambios) --}}
<template id="plantilla-nueva-fila">
    <tr class="fila-nueva-en-edicion bg-yellow-50 hover:bg-yellow-100 transition">
        @foreach($columnas as $columna)
            <td class="px-6 py-4 whitespace-nowrap" data-columna-id="{{ $columna->id }}">
                <input type="text" class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" placeholder="{{ $columna->nombre_display }}">
            </td>
        @endforeach
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
            <button type="button" class="text-green-600 hover:text-green-900 font-semibold transition btn-guardar-nuevo">Guardar</button>
            <button type="button" class="text-gray-600 hover:text-gray-900 font-semibold transition btn-cancelar-nuevo">Cancelar</button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- JS para TomSelect --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tablaGestion = document.getElementById('tabla-gestion-datos');
    const todasLasColumnasOpcionales = @json($columnas->where('es_fijo', false)->pluck('id'));
    
    new TomSelect('#filtro-columnas', {
        plugins: ['remove_button'],
        onChange: function(selectedIds) {
            const idsToShow = selectedIds.map(id => parseInt(id));
            todasLasColumnasOpcionales.forEach(colId => {
                const cells = tablaGestion.querySelectorAll(`[data-columna-id="${colId}"]`);
                if (idsToShow.includes(colId)) {
                    cells.forEach(cell => cell.classList.remove('hidden'));
                } else {
                    cells.forEach(cell => cell.classList.add('hidden'));
                }
            });
        }
    });

    // SECCIÓN: LÓGICA DE GESTIÓN DE FILAS
    const tablaCuerpo = document.getElementById('tabla-cuerpo');
    const btnAnadirFila = document.getElementById('btn-anadir-fila');
    const plantilla = document.getElementById('plantilla-nueva-fila');
    const userId = {{ $usuario->id }};

    btnAnadirFila.addEventListener('click', function() {
        if (document.querySelector('tr.fila-en-edicion') || document.querySelector('tr.fila-nueva-en-edicion')) {
            Swal.fire('Atención', 'Por favor, guarda o cancela la fila actual antes de añadir una nueva.', 'warning');
            return;
        }
        const placeholder = document.getElementById('fila-vacia-placeholder');
        if (placeholder) placeholder.remove();
        
        const nuevaFila = plantilla.content.cloneNode(true);
        const fila = nuevaFila.firstElementChild;
        
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const colId = parseInt(td.dataset.columnaId);
            // Si la columna está oculta, ocultar el input también
            const headCell = tablaGestion.querySelector(`th[data-columna-id="${colId}"]`);
            if (headCell && headCell.classList.contains('hidden')) {
                td.classList.add('hidden');
            }
        });
        
        tablaCuerpo.prepend(fila);
    });

    tablaCuerpo.addEventListener('click', function(event) {
        const target = event.target;
        if (target.classList.contains('btn-guardar-nuevo')) { guardarNuevaFila(target); return; }
        if (target.classList.contains('btn-cancelar-nuevo')) { cancelarNuevaFila(target); return; }
        if (target.classList.contains('btn-eliminar')) { confirmarEliminacion(target); return; }
        if (target.classList.contains('btn-editar')) { modoEdicion(target); return; }
        if (target.classList.contains('btn-guardar')) { guardarCambios(target); return; }
    });

    function guardarNuevaFila(boton) {
        const fila = boton.closest('tr');
        const nuevosDatos = {};
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const colId = parseInt(td.dataset.columnaId);
            const valor = td.querySelector('input').value.trim();
            if (valor) { nuevosDatos[colId] = valor; }
        });
        if (Object.keys(nuevosDatos).length === 0) {
            Swal.fire('Error', 'Por favor, completa al menos un campo.', 'error');
            return;
        }
        boton.disabled = true;
        boton.innerHTML = 'Guardando...';
        fetch(`{{ route('admin.gestion-datos.store', $usuario->id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ datos: nuevosDatos })
        })
        .then(response => {
            if (!response.ok) { return response.json().then(err => { throw err; }); }
            return response.json();
        })
        .then(data => {
            Swal.fire('¡Guardado!', data.message, 'success').then(() => { location.reload(); });
        })
        .catch(error => {
            const errorMessage = error.errors ? Object.values(error.errors).map(e => e[0]).join('\n') : (error.message || 'No se pudieron guardar los datos.');
            Swal.fire('Error', errorMessage, 'error');
            boton.disabled = false;
            boton.innerHTML = 'Guardar';
        });
    }

    function cancelarNuevaFila(boton) {
        const fila = boton.closest('tr');
        fila.remove();
    }

    function confirmarEliminacion(boton) {
        const filaId = boton.dataset.filaId;
        const url = `/admin/datos-crudos/${filaId}`;
        Swal.fire({
            title: '¿Estás absolutamente seguro?',
            text: "Esta acción eliminará todos los datos de esta fila.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡elimínalo!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }})
                .then(response => {
                    if (!response.ok) { return response.json().then(err => { throw new Error(err.message || 'Error en el servidor'); }); }
                    return response.json();
                })
                .then(data => {
                    document.getElementById(`fila-${filaId}`).remove();
                    Swal.fire('¡Eliminado!', data.message, 'success');
                })
                .catch(error => Swal.fire('Error', error.message, 'error'));
            }
        });
    }

    function modoEdicion(botonEditar) {
        const filaId = botonEditar.dataset.filaId;
        const fila = document.getElementById(`fila-${filaId}`);
        if (document.querySelector('tr.fila-en-edicion') || document.querySelector('tr.fila-nueva-en-edicion')) {
            Swal.fire('Atención', 'Por favor, guarda o cancela la edición de la otra fila.', 'warning');
            return;
        }
        fila.classList.add('fila-en-edicion');
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const valorActual = td.textContent.trim();
            td.innerHTML = `<input type="text" class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" value="${valorActual}">`;
        });
        botonEditar.textContent = 'Guardar';
        botonEditar.classList.remove('btn-editar', 'text-indigo-600');
        botonEditar.classList.add('btn-guardar', 'text-green-600');
        fila.querySelector('.btn-eliminar').style.display = 'none';
    }

    function guardarCambios(botonGuardar) {
        const filaId = botonGuardar.dataset.filaId;
        const fila = document.getElementById(`fila-${filaId}`);
        const url = `/admin/datos-crudos/${filaId}`;
        const nuevosDatos = {};
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const colId = parseInt(td.dataset.columnaId);
            nuevosDatos[colId] = td.querySelector('input').value;
        });
        botonGuardar.disabled = true;
        botonGuardar.innerHTML = 'Guardando...';
        fetch(url, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ datos: nuevosDatos, user_id: userId })
        })
        .then(response => {
            if (!response.ok) { return response.json().then(err => { throw err; }); }
            return response.json();
        })
        .then(data => {
            revertirFilaAEstadoNormal(fila, data.datos);
            Swal.fire('¡Guardado!', data.message, 'success');
        })
        .catch(error => {
            const errorMessage = error.errors ? Object.values(error.errors).join('\n') : (error.message || 'No se pudieron guardar los cambios.');
            Swal.fire('Error', errorMessage, 'error');
            botonGuardar.disabled = false;
            botonGuardar.innerHTML = 'Guardar';
        });
    }

    function revertirFilaAEstadoNormal(fila, datosActualizados) {
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const colId = parseInt(td.dataset.columnaId);
            td.textContent = datosActualizados[colId] ?? '';
        });
        const boton = fila.querySelector('.btn-guardar');
        boton.textContent = 'Editar';
        boton.disabled = false;
        boton.classList.remove('btn-guardar', 'text-green-600');
        boton.classList.add('btn-editar', 'text-indigo-600');
        const botonEliminar = fila.querySelector('.btn-eliminar');
        if (botonEliminar) botonEliminar.style.display = 'inline';
        fila.classList.remove('fila-en-edicion');
    }
});
</script>
@endpush
