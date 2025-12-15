@extends('layouts.app')

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="w-full min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-8 px-4">
    <div class="container mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Subir y Procesar Archivo Histórico</h1>
            <p class="text-gray-600">Procesa archivos para unificar los datos en el sistema.</p>
        </div>
        
        {{-- 🔥 UBICACIÓN ÚNICA Y CORRECTA PARA LAS ALERTAS 🔥 --}}
        @include('partials.alerts')

        <div class="max-w-4xl mx-auto mb-6 text-center">
            <button type="button" onclick="openModal('modalNuevoUsuario')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-blue-700 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 shadow-lg transform hover:scale-105 transition">
                ¿Es un usuario nuevo sin datos históricos? Haz clic aquí para registrarlo.
            </button>
        </div>

        {{-- 🔥 BLOQUE DE MENSAJES DUPLICADO ELIMINADO DE AQUÍ 🔥 --}}
        
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-700 px-8 py-6">
                    <h2 class="text-xl font-semibold text-white">Información del Documento a Procesar</h2>
                </div>
               
                <form action="{{ route('ingesta.store') }}" method="POST" enctype="multipart/form-data" class="p-8">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">1. Datos del Usuario (del documento)</h3>
                            <div>
                                <label for="dni" class="block text-sm font-medium text-gray-700 mb-2">DNI *</label>
                                <input type="text" name="dni" id="dni" maxlength="8" value="{{ old('dni') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="12345678">
                                <div id="dniStatus" class="mt-2 text-sm"></div>
                            </div>
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                                <div class="relative">
                                    <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" readonly required class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" placeholder="Se autocompleta con DNI válido">
                                </div>
                            </div>
                            <div>
                                <label for="codigomodular" class="block text-sm font-medium text-gray-700 mb-2">Código Modular</label>
                                <input type="text" name="codigomodular" id="codigomodular" value="{{ old('codigomodular') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Opcional si ya existe el usuario">
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">2. Archivo Excel *</h3>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <label for="archivo" class="cursor-pointer">
                                <span id="file-label" class="text-lg font-medium text-blue-600">Seleccionar archivo Excel</span>
                                <p class="text-gray-500 mt-2">Formatos: .xlsx, .xls (máx. 10MB)</p>
                                <input type="file" name="archivo" id="archivo" class="hidden" required accept=".xlsx,.xls">
                            </label>
                        </div>
                         @error('archivo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-8 text-right">
                        <button type="submit" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-indigo-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            Subir y Procesar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 🔥 MODAL CORREGIDO CON CLASES DE CENTRADO 🔥 --}}
<div id="modalNuevoUsuario" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="relative bg-white rounded-lg shadow-xl p-8 w-full max-w-lg">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Registrar Nuevo Usuario</h3>
        <form action="{{ route('users.storeMinimal') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <label for="modal_dni" class="block text-sm font-medium text-gray-700 mb-2">DNI *</label>
                    <input type="text" name="dni" id="modal_dni" maxlength="8" value="{{ old('dni') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="12345678">
                    <div id="modal_dniStatus" class="mt-2 text-sm"></div>
                    @error('dni')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label for="modal_nombre" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                    <input type="text" name="nombre" id="modal_nombre" value="{{ old('nombre') }}" readonly required class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" placeholder="Se autocompleta">
                    @error('nombre')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label for="modal_codigomodular" class="block text-sm font-medium text-gray-700 mb-2">Código Modular *</label>
                    <input type="text" name="codigomodular" id="modal_codigomodular" value="{{ old('codigomodular') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    @error('codigomodular')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="mt-8 flex justify-end space-x-4">
                <button type="button" onclick="closeModal('modalNuevoUsuario')" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Registrar Usuario</button>
            </div>
        </form>
        <button onclick="closeModal('modalNuevoUsuario')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800">&times;</button>
    </div>
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- FUNCIONES PARA MANEJAR EL MODAL ---
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    window.openModal = openModal; // Hacemos la función global para que el 'onclick' la vea

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    window.closeModal = closeModal; // Hacemos la función global

    // --- LÓGICA PARA EL LABEL DEL ARCHIVO (SIN CAMBIOS) ---
    const archivoInput = document.getElementById('archivo');
    if (archivoInput) {
        archivoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileLabel = document.getElementById('file-label');
            if (file) {
                fileLabel.textContent = file.name;
            } else {
                fileLabel.textContent = 'Seleccionar archivo Excel';
            }
        });
    }

    // 🔥 ========================================================== 🔥
    // 🔥 FUNCIÓN REUTILIZABLE PARA LA CONSULTA DE DNI 🔥
    // 🔥 ========================================================== 🔥
    function inicializarConsultaDNI(dniInputId, nombreInputId, statusDivId) {
        const dniInput = document.getElementById(dniInputId);
        const nombreInput = document.getElementById(nombreInputId);
        const dniStatus = document.getElementById(statusDivId);
        // El spinner es opcional, lo manejaremos con texto
        
        if (!dniInput) return; // Si el campo no existe, no hacemos nada

        let dniTimeout;

        dniInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
            clearTimeout(dniTimeout);
            dniStatus.textContent = '';
            
            if (value.length === 8) {
                dniTimeout = setTimeout(() => {
                    obtenerDatos(value);
                }, 500);
            } else {
                nombreInput.value = '';
            }
        });

        async function obtenerDatos(dni) {
            dniStatus.textContent = 'Consultando...';
            dniStatus.className = 'text-gray-500 text-sm mt-2';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('{{ route("dni.obtener") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ dni: dni }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    nombreInput.value = data.nombre_completo;
                    dniStatus.textContent = 'DNI válido - Datos cargados.';
                    dniStatus.className = 'text-green-600 text-sm mt-2';
                } else {
                    nombreInput.value = '';
                    dniStatus.textContent = data.message || 'Error al consultar DNI.';
                    dniStatus.className = 'text-red-600 text-sm mt-2';
                }
            } catch (error) {
                nombreInput.value = '';
                dniStatus.textContent = 'Error de conexión. Inténtalo de nuevo.';
                dniStatus.className = 'text-yellow-600 text-sm mt-2';
            }
        }
    }

    // --- INICIALIZAMOS LA FUNCIONALIDAD EN AMBOS FORMULARIOS ---
    
    // 1. Para el formulario principal de ingesta
    inicializarConsultaDNI('dni', 'nombre', 'dniStatus');

    // 2. Para el nuevo modal de registro
    inicializarConsultaDNI('modal_dni', 'modal_nombre', 'modal_dniStatus');

});
</script>
@endpush