@extends('layouts.app')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <div>
                <a href="{{ route('admin.gestion-datos.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border rounded-lg hover:bg-gray-100">&larr; Volver a la Búsqueda</a>
                <button id="btn-anadir-fila" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-lg ml-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Añadir Registro
                </button>
            </div>
        </div>

        <!-- Alertas -->
        @include('partials.alerts')

        <!-- Tabla de Datos -->
        <div class="bg-white rounded-2xl shadow-lg border">
            <div class="overflow-x-auto max-h-[48rem]">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    @foreach($columnas as $columna)
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $columna->nombre_display }}</th>
                    @endforeach
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tabla as $idFilaOrigen => $fila)
                    <tr id="fila-{{ $idFilaOrigen }}">
                    @foreach($columnas as $columna)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" data-columna-id="{{ $columna->id }}">
                        {{ $fila['datos'][$columna->id] ?? '' }}
                        </td>
                    @endforeach
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button type="button" class="text-indigo-600 hover:text-indigo-900 btn-editar" data-fila-id="{{ $idFilaOrigen }}">Editar</button>
                        <button type="button" class="text-red-600 hover:text-red-900 ml-4 btn-eliminar" data-fila-id="{{ $idFilaOrigen }}">Eliminar</button>
                    </td>
                    </tr>
                @empty
                    <tr>
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

{{-- Template for new row creation --}}
<template id="plantilla-nueva-fila">
    <tr class="fila-nueva-en-edicion bg-yellow-50">
        @foreach($columnas as $columna)
            <td class="px-6 py-4 whitespace-nowrap" data-columna-id="{{ $columna->id }}">
                <input type="text" class="w-full border border-gray-300 rounded-md shadow-sm p-2" placeholder="{{ $columna->nombre_display }}">
            </td>
        @endforeach
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
            <button type="button" class="text-green-600 hover:text-green-900 btn-guardar-nuevo">Guardar</button>
            <button type="button" class="text-gray-600 hover:text-gray-900 ml-4 btn-cancelar-nuevo">Cancelar</button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabla = document.querySelector('tbody');
    const btnAnadirFila = document.getElementById('btn-anadir-fila');
    const plantilla = document.getElementById('plantilla-nueva-fila');
    const userId = {{ $usuario->id }};

    btnAnadirFila.addEventListener('click', function() {
        console.log('[v0] Add row button clicked');
        const otraFilaEditando = document.querySelector('tr.fila-nueva-en-edicion');
        if (otraFilaEditando) {
            Swal.fire('Atención', 'Por favor, guarda o cancela la edición de la otra fila antes de añadir una nueva.', 'warning');
            return;
        }
        const nuevaFila = plantilla.content.cloneNode(true);
        tabla.appendChild(nuevaFila);
        console.log('[v0] New row added to table');
    });

    tabla.addEventListener('click', function(event) {
        const target = event.target;
        
        if (target.classList.contains('btn-guardar-nuevo')) {
            guardarNuevaFila(target);
            return;
        }
        
        if (target.classList.contains('btn-cancelar-nuevo')) {
            cancelarNuevaFila(target);
            return;
        }
        
        if (target.classList.contains('btn-eliminar')) {
            confirmarEliminacion(target);
            return;
        }
        if (target.classList.contains('btn-editar')) {
            modoEdicion(target);
            return;
        }
        if (target.classList.contains('btn-guardar')) {
            guardarCambios(target);
            return;
        }
    });

    function guardarNuevaFila(boton) {
        console.log('[v0] Save new row');
        const fila = boton.closest('tr');
        const nuevosDatos = {};

        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const columnaId = td.dataset.columnaId;
            const valor = td.querySelector('input').value.trim();
            if (valor) {
                nuevosDatos[columnaId] = valor;
            }
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
            console.log('[v0] Response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => { 
                    console.error('[v0] Error response:', err);
                    throw err; 
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('[v0] Data saved successfully:', data);
            Swal.fire('¡Guardado!', data.message, 'success').then(() => {
                location.reload();
            });
        })
        .catch(error => {
            console.error('[v0] Save error:', error);
            const errorMessage = error.errors ? Object.values(error.errors).join('\n') : (error.message || 'No se pudieron guardar los datos.');
            Swal.fire('Error', errorMessage, 'error');
            boton.disabled = false;
            boton.innerHTML = 'Guardar';
        });
    }

    function cancelarNuevaFila(boton) {
        console.log('[v0] Cancel new row');
        const fila = boton.closest('tr');
        fila.remove();
    }

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
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Error en el servidor'); });
                    }
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
        const otraFilaEditando = document.querySelector('tr.fila-en-edicion');
        if (otraFilaEditando) {
            Swal.fire('Atención', 'Por favor, guarda o cancela la edición de la otra fila antes de editar una nueva.', 'warning');
            return;
        }
        fila.classList.add('fila-en-edicion');
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const valorActual = td.textContent.trim();
            td.innerHTML = `<input type="text" class="w-full border border-gray-300 rounded-md shadow-sm p-2" value="${valorActual}">`;
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
            const columnaId = td.dataset.columnaId;
            nuevosDatos[columnaId] = td.querySelector('input').value;
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
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            revertirFilaAEstadoNormal(fila, data.datos);
            Swal.fire('¡Guardado!', data.message, 'success');
        })
        .catch(error => {
            console.error('Error al guardar:', error);
            const errorMessage = error.errors ? Object.values(error.errors).join('\n') : (error.message || 'No se pudieron guardar los cambios.');
            Swal.fire('Error', errorMessage, 'error');
            botonGuardar.disabled = false;
            botonGuardar.innerHTML = 'Guardar';
        });
    }

    function revertirFilaAEstadoNormal(fila, datosActualizados) {
        fila.querySelectorAll('td[data-columna-id]').forEach(td => {
            const columnaId = td.dataset.columnaId;
            td.textContent = datosActualizados[columnaId] ?? '';
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
