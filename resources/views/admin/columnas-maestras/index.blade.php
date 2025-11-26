@extends('layouts.app')
@push('head')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
@section('content')
<div class="w-full min-h-screen bg-slate-50 py-8 px-4">
    <div class="container mx-auto">
        <!-- Header y Botón de Crear -->
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Gestión de Columnas Maestras</h1>
                <p class="text-gray-600">Administra el diccionario global de columnas del sistema.</p>
            </div>
            <button onclick="openModal('modalNuevaColumna')" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nueva Columna
            </button>
        </div>

        <!-- Alertas -->
        @include('partials.alerts') 

        <!-- Tabla de Columnas -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase">Nombre a Mostrar</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase">Nombre Normalizado</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase">¿Es Fijo?</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($columnas as $columna)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-800">{{ $columna->nombre_display }}</p>
                                    <p class="text-sm text-gray-500">{{ Str::limit($columna->descripcion, 50) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <code class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm">{{ $columna->nombre_normalizado }}</code>
                                </td>
                                <td class="px-6 py-4">
                                    @if($columna->es_fijo)
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Sí</span>
                                    @else
                                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">No</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button onclick="openEditModal({{ $columna->toJson() }})" class="p-2 text-gray-500 hover:text-blue-600 rounded-full hover:bg-gray-200" title="Editar Columna">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button onclick="confirmDelete({{ $columna->id }})" class="p-2 text-gray-500 hover:text-red-600 rounded-full hover:bg-gray-200" title="Eliminar Columna">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-12 text-gray-500">No se encontraron columnas maestras.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t">
                 {{ $columnas->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
@include('admin.columnas-maestras.modal-create')
@include('admin.columnas-maestras.modal-edit')

<form id="formEliminarColumna" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
// --- MANEJO DE MODALES ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    // Forzamos a Tailwind a aplicar las clases de centrado
    modal.classList.add('flex', 'items-center', 'justify-center');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex', 'items-center', 'justify-center');
}

// --- LÓGICA DE EDICIÓN ---
function openEditModal(columna) {
    const url = `{{ url('admin/columnas-maestras') }}/${columna.id}`;
    const form = document.getElementById('formEditarColumna');
    
    form.action = url;
    form.querySelector('#edit_nombre_display').value = columna.nombre_display;
    form.querySelector('#edit_nombre_normalizado').value = columna.nombre_normalizado;
    form.querySelector('#edit_descripcion').value = columna.descripcion || '';
    form.querySelector('#edit_es_fijo').value = columna.es_fijo ? '1' : '0';
    
    // 🔥 CORRECCIÓN: Llenamos el campo oculto con el ID
    form.querySelector('#edit_id_field').value = columna.id;

    openModal('modalEditarColumna');
}

// --- LÓGICA DE ELIMINACIÓN ---
function confirmDelete(id) {
    // Depuración: Verificamos que los datos llegan bien
    console.log('ID para eliminar:', id);
    const url = `{{ url('admin/columnas-maestras') }}/${id}`;
    console.log('URL de destino:', url);

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
        // Si el usuario hace clic en "Sí, ¡elimínalo!"
        if (result.isConfirmed) {
            // Seleccionamos el formulario, le ponemos la URL correcta y lo enviamos
            const form = document.getElementById('formEliminarColumna');
            form.action = url;
            form.submit();
        }
    });
}


// --- REAPERTURA DE MODAL EN CASO DE ERROR DE VALIDACIÓN ---
@if ($errors->any())
    document.addEventListener('DOMContentLoaded', function () {
        // 🔥 Usamos la variable de sesión que envía el controlador
        @if (session('error_form_type') === 'edit')
            // Reconstruimos el objeto 'columna' con los datos 'old' para pasarlo a la función
            const oldColumnaData = {
                id: "{{ old('id') }}",
                nombre_display: "{{ old('nombre_display') }}",
                nombre_normalizado: "{{ old('nombre_normalizado') }}",
                descripcion: "{{ old('descripcion') }}",
                es_fijo: "{{ old('es_fijo') }}" === '1'
            };
            openEditModal(oldColumnaData);
        @else
            openModal('modalNuevaColumna');
        @endif
    });
@endif
</script>
@endpush