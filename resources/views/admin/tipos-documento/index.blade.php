@extends('layouts.app')

@push('head')
    {{-- SweetAlert2 para los modales de confirmación de eliminación --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Gestión de Tipos de Documento</h1>
                    <p class="text-gray-600">Administra los diferentes tipos de documentos del sistema</p>
                </div>
                <button onclick="openModal('modalNuevoTipo')" 
                        class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Nuevo Tipo
                </button>
            </div>
        </div>

        <!-- Alertas de Sesión (Éxito y Error) -->
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                <strong class="font-bold">¡Éxito!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                <strong class="font-bold">¡Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        
        <!-- Alerta de Errores de Validación (para los modales) -->
        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                <strong class="font-bold">Por favor, corrige los errores:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            {{-- Total de Tipos --}}
            <div class="bg-white rounded-xl shadow-lg p-6"><h3 class="text-2xl font-bold text-gray-800">{{ $tipos->count() }}</h3><p class="text-gray-600">Total de Tipos</p></div>
            {{-- Constancias Asociadas --}}
            <div class="bg-white rounded-xl shadow-lg p-6"><h3 class="text-2xl font-bold text-gray-800">{{ $tipos->sum('constancias_count') }}</h3><p class="text-gray-600">Constancias Asociadas</p></div>
            {{-- Último Agregado --}}
            <div class="bg-white rounded-xl shadow-lg p-6"><h3 class="text-lg font-bold text-gray-800">{{ optional($tipos->last())->created_at ?? 'N/A' }}</h3><p class="text-gray-600">Último Agregado</p></div>
        </div>

        <!-- Cuadrícula de Tipos de Documento -->
        @if($tipos->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($tipos as $tipo)
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 flex flex-col">
                        <div class="p-6 flex-grow">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $tipo->nombre }}</h3>
                            <p class="text-gray-600 mb-4 min-h-[3rem]">{{ $tipo->descripcion ?: 'Sin descripción.' }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 border-t flex items-center justify-between">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">{{ $tipo->constancias_count }} constancia(s)</span>
                            <div class="flex items-center space-x-2">
                                <button onclick="openEditModal({{ $tipo->id }}, '{{ addslashes($tipo->nombre) }}', '{{ addslashes($tipo->descripcion) }}')" class="p-2 text-gray-500 hover:text-indigo-600 rounded-full hover:bg-gray-200 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button onclick="confirmDelete({{ $tipo->id }})" class="p-2 text-gray-500 hover:text-red-600 rounded-full hover:bg-gray-200 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Estado Vacío -->
            <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No hay tipos registrados</h3>
                <p class="text-gray-600 mb-6">Comienza creando tu primer tipo de documento.</p>
                <button onclick="openModal('modalNuevoTipo')" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg">Crear Primer Tipo</button>
            </div>
        @endif
    </div>

    <div class="mt-16 bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Generador de Plantillas</h2>
    <p class="text-gray-600 mb-6">Selecciona las columnas que deseas incluir en tu plantilla de Excel y descárgala.</p>

    <form action="{{ route('admin.plantillas.generarPersonalizada') }}" method="POST">
        @csrf
        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Columna de Selección de Campos --}}
            <div class="lg:col-span-2">
                @php
                    $prioritarios = ['meses', 'ano', 'total_remuneracion', 'total_descuento', 'observacion', 'reint_', 'neto_a_pagar'];
                @endphp

                {{-- Campos Prioritarios --}}
                <h3 class="font-semibold text-gray-700 mb-3">Campos Requeridos</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 p-4 border rounded-lg bg-gray-50 mb-6">
                    @foreach($columnasMaestras as $columna)
                        @if(in_array($columna->nombre_normalizado, $prioritarios))
                            <div class="flex items-center">
                                <input type="checkbox" id="col-{{$columna->id}}" value="{{$columna->id}}" class="h-4 w-4" checked disabled>
                                <input type="hidden" name="columnas[]" value="{{ $columna->id }}">
                                <label for="col-{{$columna->id}}" class="ml-2 text-sm font-medium text-gray-700">{{ $columna->nombre_display }}</label>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Campos Opcionales --}}
                <h3 class="font-semibold text-gray-700 mb-3">Campos Opcionales</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 p-4 border rounded-lg">
                    @foreach($columnasMaestras as $columna)
                        @if(!in_array($columna->nombre_normalizado, $prioritarios))
                            <div class="flex items-center">
                                <input type="checkbox" name="columnas[]" id="col-{{$columna->id}}" value="{{$columna->id}}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="col-{{$columna->id}}" class="ml-2 text-sm text-gray-700">{{ $columna->nombre_display }}</label>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Columna de Previsualización y Descarga --}}
            <div class="bg-gray-50 border rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Columnas Seleccionadas</h3>
                <ul id="lista-preview" class="space-y-2 text-sm text-gray-600 max-h-60 overflow-y-auto mb-6">
                    {{-- El JavaScript llenará esta lista --}}
                </ul>
                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Descargar Plantilla
                </button>
            </div>
        </div>
    </form>
</div>


</div>

<!-- Modal para Crear Nuevo Tipo -->
<div id="modalNuevoTipo" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4" id="modalNuevoTipoContent">
        <form action="{{ route('admin.tipos-documento.store') }}" method="POST">
            @csrf
            <div class="p-6 border-b"><h3 class="text-xl font-semibold">Nuevo Tipo de Documento</h3></div>
            <div class="p-6 space-y-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium mb-1">Nombre *</label>
                    <input type="text" name="nombre" id="nombre" required value="{{ old('nombre') }}" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="descripcion" class="block text-sm font-medium mb-1">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3" class="w-full border-gray-300 rounded-lg">{{ old('descripcion') }}</textarea>
                </div>
            </div>
            <div class="p-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('modalNuevoTipo')" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Crear Tipo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Editar Tipo -->
<div id="modalEditarTipo" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4" id="modalEditarTipoContent">
        <form id="formEditarTipo" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 border-b"><h3 class="text-xl font-semibold">Editar Tipo de Documento</h3></div>
            <div class="p-6 space-y-4">
                <div>
                    <label for="editNombre" class="block text-sm font-medium mb-1">Nombre *</label>
                    <input type="text" name="nombre" id="editNombre" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="editDescripcion" class="block text-sm font-medium mb-1">Descripción</label>
                    <textarea name="descripcion" id="editDescripcion" rows="3" class="w-full border-gray-300 rounded-lg"></textarea>
                </div>
            </div>
            <div class="p-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('modalEditarTipo')" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario oculto para la eliminación -->
<form id="formEliminarTipo" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
// Funciones para manejar los modales (abrir/cerrar)
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.getElementById(modalId).classList.add('flex');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}

// Llenar y abrir el modal de edición
function openEditModal(id, nombre, descripcion) {
    // Construimos la URL usando el helper de rutas de Laravel
    const url = "{{ route('admin.tipos-documento.update', ':id') }}".replace(':id', id);
    document.getElementById('formEditarTipo').action = url;
    document.getElementById('editNombre').value = nombre;
    document.getElementById('editDescripcion').value = descripcion || '';
    openModal('modalEditarTipo');
}

// Confirmar y enviar el formulario de eliminación
function confirmDelete(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, ¡elimínalo!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = "{{ route('admin.tipos-documento.destroy', ':id') }}".replace(':id', id);
            const form = document.getElementById('formEliminarTipo');
            form.action = url;
            form.submit();
        }
    })
}

// Lógica para reabrir el modal correcto si falla la validación del servidor
@if ($errors->any())
    // Comprobamos qué formulario se envió revisando el 'old input'
    @if (old('form_type') === 'edit')
        document.addEventListener('DOMContentLoaded', function () {
            // Reconstruimos los datos para reabrir el modal de edición
            openEditModal("{{ old('id') }}", "{{ old('nombre') }}", "{{ old('descripcion') }}");
        });
    @else
        document.addEventListener('DOMContentLoaded', function () {
            openModal('modalNuevoTipo');
        });
    @endif
@endif

// Añadir un campo oculto a los formularios para saber cuál se envió
document.getElementById('modalNuevoTipo').querySelector('form').insertAdjacentHTML('beforeend', '<input type="hidden" name="form_type" value="create">');
document.getElementById('modalEditarTipo').querySelector('form').insertAdjacentHTML('beforeend', '<input type="hidden" name="form_type" value="edit"><input type="hidden" name="id" id="editId">');

// Actualizar el ID en el campo oculto del form de edición
function openEditModal(id, nombre, descripcion) {
    const url = "{{ route('admin.tipos-documento.update', ':id') }}".replace(':id', id);
    document.getElementById('formEditarTipo').action = url;
    document.getElementById('editNombre').value = nombre;
    document.getElementById('editDescripcion').value = descripcion || '';
    document.getElementById('editId').value = id; // <-- Actualizamos el ID aquí
    openModal('modalEditarTipo');
}
const checkboxes = document.querySelectorAll('form[action="{{ route('admin.plantillas.generarPersonalizada') }}"] input[type="checkbox"]');
const previewList = document.getElementById('lista-preview');

function actualizarPreview() {
    previewList.innerHTML = ''; // Limpiamos la lista
    let hasSelection = false;
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            hasSelection = true;
            const label = document.querySelector(`label[for="${checkbox.id}"]`);
            const listItem = document.createElement('li');
            listItem.textContent = label.textContent;
            listItem.classList.add('px-2', 'py-1', 'bg-white', 'rounded', 'border');
            previewList.appendChild(listItem);
        }
    });

    if (!hasSelection) {
        previewList.innerHTML = '<li class="text-gray-400">Selecciona al menos un campo opcional.</li>';
    }
}

checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', actualizarPreview);
});

// Llamamos a la función una vez al cargar la página para mostrar los prioritarios
actualizarPreview();
</script>
@endsection