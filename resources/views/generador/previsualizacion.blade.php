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
                                @role('admin')
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                @endrole
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
                                    @role('admin')
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button" class="text-indigo-600 hover:text-indigo-900 btn-editar" data-fila-id="{{ $idFilaOrigen }}">Editar</button>
                                            <button type="button" class="text-red-600 hover:text-red-900 ml-4 btn-eliminar" data-fila-id="{{ $idFilaOrigen }}">Eliminar</button>
                                        </td>
                                    @endrole
                                </tr>
                            @empty
                                <tr>
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
                                    <option value="{{ $tipo->id }}" @selected(old('tipo_documento_id') == $tipo->id)>{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        {{-- BUSCADOR DE CONSTANCIAS MEJORADO --}}
                        <div>
                            <label for="constancia_search" class="block text-sm font-medium text-gray-700 mb-1">Número de Constancia (Selecciona o Crea una nueva) *</label>
                            <select id="constancia_search" name="numero_constancia" required placeholder="Escribe para buscar o crear..."></select>
                        </div>

                        {{-- 🔥 CAMPO NÚMERO DE EXPEDIENTE (VERSIÓN ÚNICA Y CORREGIDA) 🔥 --}}
                        <div>
                            <label for="numero_expediente" class="block text-sm font-medium text-gray-700 mb-1">Número de Nuevo Expediente *</label>
                            <input type="text" name="numero_expediente" id="numero_expediente" value="{{ old('numero_expediente') }}" required class="w-full border-gray-300 rounded-lg shadow-sm" placeholder="Ej: EXP-2025-001">
                            
                            {{-- Aquí mostraremos el mensaje de la verificación AJAX --}}
                            <p id="feedback-expediente" class="mt-2 text-sm"></p>
                            
                            {{-- Aquí se mostrará el error de Laravel si la validación del backend falla --}}
                            @error('numero_expediente')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
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

{{-- 🔥 =================================================================== 🔥 --}}
{{-- 🔥 SECCIÓN DE SCRIPTS UNIFICADA Y CORREGIDA 🔥 --}}
{{-- 🔥 =================================================================== 🔥 --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DE TOM-SELECT PARA CONSTANCIAS (SIN CAMBIOS) ---
    new TomSelect('#constancia_search', {
        valueField: 'numero_constancia',
        labelField: 'numero_constancia',
        searchField: ['numero_constancia'],
        create: true,
        render: {
            option: function(item, escape) {
                const isNew = typeof item.id === 'undefined';
                const newTag = isNew ? '<span class="text-xs text-green-500 font-semibold ml-2">[NUEVO]</span>' : '';
                return `<div><span class="font-medium">${escape(item.numero_constancia)}</span>${newTag}</div>`;
            },
            item: function(item, escape) {
                return `<div>${escape(item.numero_constancia)}</div>`;
            },
            no_results:function(data,escape){
                return '<div class="p-2">No se encontraron constancias. Escribe y presiona Enter para crearla.</div>';
            },
        },
        load: function(query, callback) {
            if (!query.length) return callback();
            const url = `{{ route('api.constancias.buscar') }}?q=${encodeURIComponent(query)}`;
            fetch(url)
                .then(response => response.json())
                .then(json => { callback(json); })
                .catch(() => { callback(); });
        },
        loadThrottle: 300,
    });

    // --- LÓGICA PARA LA VERIFICACIÓN EN TIEMPO REAL DEL NÚMERO DE EXPEDIENTE ---
    const inputExpediente = document.getElementById('numero_expediente');
    const feedbackElement = document.getElementById('feedback-expediente');
    const submitButton = document.getElementById('submit-button');
    let typingTimer;
    const doneTypingInterval = 500;

    inputExpediente.addEventListener('keyup', () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(verificarNumeroExpediente, doneTypingInterval);
    });

    async function verificarNumeroExpediente() {
        const numero = inputExpediente.value.trim();
        feedbackElement.textContent = '';
        feedbackElement.className = 'mt-2 text-sm';

        if (numero.length < 3) {
            submitButton.disabled = false;
            return;
        }

        feedbackElement.textContent = 'Verificando...';
        feedbackElement.classList.add('text-gray-500');

        try {
            const response = await fetch("{{ route('api.expedientes.verificar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ numero_expediente: numero })
            });

            if (!response.ok) throw new Error('Error en la conexión.');

            const data = await response.json();

            if (data.existe) {
                feedbackElement.textContent = 'Este número de expediente ya existe. Ingrese otro.';
                feedbackElement.classList.remove('text-green-600', 'text-gray-500');
                feedbackElement.classList.add('text-red-600');
                inputExpediente.classList.add('border-red-500');
                submitButton.disabled = true;
            } else {
                feedbackElement.textContent = 'Número de expediente disponible.';
                feedbackElement.classList.remove('text-red-600', 'text-gray-500');
                feedbackElement.classList.add('text-green-600');
                inputExpediente.classList.remove('border-red-500');
                submitButton.disabled = false;
            }
        } catch (error) {
            feedbackElement.textContent = 'No se pudo verificar el número. Inténtelo de nuevo.';
            feedbackElement.classList.add('text-yellow-600');
            submitButton.disabled = false;
        }
    }
});
</script>

@role('admin')
<script>
// --- LÓGICA PARA EDICIÓN Y ELIMINACIÓN DE FILAS (SÓLO PARA ADMINS) ---
document.addEventListener('DOMContentLoaded', function() {
    const tabla = document.querySelector('tbody');

    tabla.addEventListener('click', function(event) {
        const target = event.target;
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ datos: nuevosDatos })
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
            const errorMessage = error.errors ? Object.values(error.errors).join('\n') : 'No se pudieron guardar los cambios.';
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
        if (!document.querySelector('tr.fila-en-edicion')) {
            document.getElementById('submit-button').disabled = false;
        }
    }
});
</script>
@endrole
@endpush