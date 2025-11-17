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
                <h1 class="text-3xl font-bold text-gray-800">Gestión de Usuarios</h1>
                <p class="text-gray-600">Administra los usuarios del sistema, sus roles y accesos.</p>
            </div>
            <button onclick="openModal('modalNuevoUsuario')" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nuevo Usuario
            </button>
        </div>

        <!-- Alertas -->
        @include('partials.alerts')

        

        <!-- Búsqueda y Paginación -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border flex items-center justify-between">
            <form action="{{ route('admin.users.index') }}" method="GET" class="w-full md:w-1/3">
                <input type="text" name="search" placeholder="Buscar por nombre o email..." value="{{ request('search') }}" class="w-full border-gray-300 rounded-lg">
            </form>
            <div>{{ $usuarios->links() }}</div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase">Usuario</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase">Rol</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($usuarios as $usuario)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-4">
                                            <span class="text-indigo-600 font-semibold">{{ substr($usuario->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $usuario->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $usuario->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($usuario->roles->isNotEmpty())
                                        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">{{ $usuario->roles->first()->name }}</span>
                                    @else
                                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">Sin rol</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.users.detalle', $usuario) }}" title="Ver Detalle" class="p-2 text-gray-500 hover:text-green-600 rounded-full hover:bg-gray-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>
                                        <button onclick="openEditModal({{ $usuario->id }}, '{{ addslashes($usuario->name) }}', '{{ $usuario->email }}', '{{ $usuario->dni }}', '{{ optional($usuario->roles->first())->name }}')" class="p-2 text-gray-500 hover:text-blue-600 rounded-full hover:bg-gray-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        @if(auth()->id() !== $usuario->id)
                                            <button onclick="confirmDelete({{ $usuario->id }})" class="p-2 text-gray-500 hover:text-red-600 rounded-full hover:bg-gray-200">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-12 text-gray-500">No se encontraron usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin.users.partials.dashboard-reporte')

    </div>
</div>

<!-- Modales -->
@include('admin.users.partials.modal-create')
@include('admin.users.partials.modal-edit')

<!-- Formulario oculto para eliminar -->
<form id="formEliminarUsuario" method="POST" class="hidden">@csrf @method('DELETE')</form>

<script>
// Funciones genéricas para abrir/cerrar modales
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.getElementById(modalId).classList.add('flex');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}

// Función para poblar y abrir el modal de edición
function openEditModal(id, name, email, dni, rol) {
    // Construimos la URL del formulario dinámicamente
    const url = "{{ route('admin.users.update', ':id') }}".replace(':id', id);
    const form = document.getElementById('formEditarUsuario');
    
    // Asignamos la URL y los valores a los campos del formulario
    form.action = url;
    form.querySelector('#edit_name').value = name;
    form.querySelector('#edit_email').value = email;
    form.querySelector('#edit_dni').value = dni || '';
    form.querySelector('#edit_rol').value = rol;

    // Abrimos el modal
    openModal('modalEditarUsuario');
}

// Función para confirmar y enviar el formulario de eliminación
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
            // Construimos la URL y seleccionamos el formulario oculto
            const url = "{{ route('admin.users.destroy', ':id') }}".replace(':id', id);
            const form = document.getElementById('formEliminarUsuario');
            
            // Asignamos la URL y lo enviamos
            form.action = url;
            form.submit();
        }
    })
}

// Lógica para reabrir el modal correcto si hay un error de validación del servidor
@if ($errors->any())
    // Comprobamos si el error viene de una actualización (se envía el método PUT)
    @if (session('error_form_type') === 'edit')
        document.addEventListener('DOMContentLoaded', function () {
            // Reconstruimos los datos para reabrir el modal de edición
            // Usamos old() para repoblar con los datos que el usuario intentó enviar
            openEditModal(
                "{{ old('id') }}", 
                "{{ old('name') }}", 
                "{{ old('email') }}",
                "{{ old('dni') }}", 
                "{{ old('rol') }}"
            );
        });
    @else
        // Si no, asumimos que es el modal de creación
        document.addEventListener('DOMContentLoaded', function () {
            openModal('modalNuevoUsuario');
        });
    @endif
@endif

// Es necesario un pequeño ajuste en el controlador para que la lógica de arriba funcione.
// En el método update(), si la validación falla, Laravel no sabe qué ID se estaba editando.
// Tenemos que añadirlo al redirect.

// En el método edit() del controlador, necesitamos enviar el ID al formulario.
// Añadiremos un campo oculto a los formularios para saber cuál se envió.
document.getElementById('formEditarUsuario').insertAdjacentHTML('beforeend', '<input type="hidden" name="id" id="editId">');

// Y actualizamos el ID en el campo oculto cuando abrimos el modal
function openEditModal(id, name, email, dni, rol) {
    const url = "{{ route('admin.users.update', ':id') }}".replace(':id', id);
    const form = document.getElementById('formEditarUsuario');
    form.action = url;
    form.querySelector('#edit_name').value = name;
    form.querySelector('#edit_email').value = email;
    form.querySelector('#edit_dni').value = dni || '';
    form.querySelector('#edit_rol').value = rol;
    form.querySelector('#editId').value = id; // <-- Actualizamos el ID aquí
    openModal('modalEditarUsuario');
}
</script>
@endsection