@extends('layouts.app')

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
{{-- Este es el div que ahora controla el fondo de ESTA página específica --}}
<div class="w-full min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-8 px-4">
    <div class="container mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Subir y Procesar Archivo Histórico</h1>
            <p class="text-gray-600">Procesa archivos para unificar los datos en el sistema.</p>
        </div>

        {{-- Los mensajes de sesión se mantienen igual --}}
        @if(session('success'))
            <div class="max-w-4xl mx-auto mb-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-green-800">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="max-w-4xl mx-auto mb-6">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-red-800">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- El formulario se mantiene igual --}}
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-8 py-6">
                    <h2 class="text-xl font-semibold text-white">Información del Documento a Procesar</h2>
                </div>
               
                <form action="{{ route('ingesta.store') }}" method="POST" enctype="multipart/form-data" class="p-8">
                @csrf
                    
                    <div class="grid md:grid-cols-2 gap-8">
                        <!-- Columna Izquierda: Datos del Usuario y Documento -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">1. Datos del Usuario (del documento)</h3>
                            <div>
                                <label for="dni" class="block text-sm font-medium text-gray-700 mb-2">DNI *</label>
                                <input type="text" name="dni" id="dni" maxlength="8" value="{{ old('dni') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="12345678">
                                <div id="dniStatus" class="mt-2 text-sm hidden"></div>
                            </div>
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                                <div class="relative">
                                    <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" readonly required class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" placeholder="Se autocompleta con DNI válido">
                                    <div id="loadingSpinner" class="absolute right-4 top-1/2 transform -translate-y-1/2 hidden animate-spin">
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9a9 9 0 0114.65-5.35M20 15a9 9 0 01-14.65 5.35"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Sección de Carga de Archivo -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">3. Archivo Excel</h3>
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

                    <!-- Botón de Envío -->
                    <div class="mt-8 text-right">
                        <button type="submit" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-medium rounded-lg hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            Subir y Procesar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    // Tu excelente código JavaScript va aquí.
    // Solo he cambiado el selector del label del archivo para mayor precisión.
    const dniInput = document.getElementById('dni');
    const nombreInput = document.getElementById('nombre');
    const dniStatus = document.getElementById('dniStatus');
    const loadingSpinner = document.getElementById('loadingSpinner');
    let dniTimeout;

    dniInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        e.target.value = value;
        clearTimeout(dniTimeout);
        dniStatus.classList.add('hidden');
        if (value.length === 8) {
            dniTimeout = setTimeout(() => {
                obtenerDatos(value);
            }, 500);
        } else {
            nombreInput.value = '';
            loadingSpinner.classList.add('hidden');
        }
    });

    async function obtenerDatos(dni) {
        loadingSpinner.classList.remove('hidden');
        dniStatus.classList.add('hidden');
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
            loadingSpinner.classList.add('hidden');
            if (response.ok && data.success) {
                nombreInput.value = data.nombre_completo;
                dniStatus.innerHTML = `<svg class="w-5 h-5 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> DNI válido - Datos cargados`;
                dniStatus.className = 'text-green-600 text-sm mt-2 flex items-center';
            } else {
                nombreInput.value = '';
                dniStatus.innerHTML = `<svg class="w-5 h-5 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg> ${data.message || 'Error al consultar DNI'}`;
                dniStatus.className = 'text-red-600 text-sm mt-2 flex items-center';
            }
        } catch (error) {
            loadingSpinner.classList.add('hidden');
            nombreInput.value = '';
            dniStatus.innerHTML = `<svg class="w-5 h-5 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg> Error de conexión`;
        }
        dniStatus.classList.remove('hidden');
    }

    document.getElementById('archivo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('file-label').textContent = file.name;
        } else {
            document.getElementById('file-label').textContent = 'Seleccionar archivo Excel';
        }
    });
</script>
@endpush