

@extends('layouts.app')

@section('content')
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
{{-- 🔥 INICIO: Contenedor principal con Alpine.js 🔥 --}}
{{-- x-data inicializa el estado del modal. Si hay errores de validación, se abre automáticamente. --}}
<div x-data="{ isModalOpen: @json($errors->any()) }" class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 py-12">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Mi Perfil</h1>
            <p class="text-gray-600 mt-2">Visualiza tus datos personales y tu progreso de metas.</p>
        </div>

        <!-- Alerta de éxito (si viene del update) -->
         @if (session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span class="text-green-700 font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Columna Izquierda: Tarjeta de Información Personal -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-8">
                        {{-- ... (Contenido de la tarjeta de perfil, sin cambios) ... --}}
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center">
                                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-3xl font-bold">
                                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                </div>
                                <div class="ml-6">
                                    <h2 class="text-2xl font-bold text-gray-800">{{ $usuario->name }}</h2>
                                    <p class="text-gray-500">{{ optional($usuario->roles->first())->name ?? 'Sin rol' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t pt-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-6">Información Personal</h3>
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 rounded-lg p-4"><label class="text-sm font-medium text-gray-500 uppercase tracking-wider">Código Modular</label><p class="text-gray-800 font-medium mt-2">{{ $usuario->codigomodular ?? 'No registrado' }}</p></div>
                                <div class="bg-gray-50 rounded-lg p-4"><label class="text-sm font-medium text-gray-500 uppercase tracking-wider">DNI</label><p class="text-gray-800 font-medium mt-2">{{ $usuario->dni ?? 'No registrado' }}</p></div>
                                <div class="bg-gray-50 rounded-lg p-4 col-span-2"><label class="text-sm font-medium text-gray-500 uppercase tracking-wider">Email</label><p class="text-gray-800 font-medium mt-2">{{ $usuario->email }}</p></div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 mt-6"><label class="text-sm font-medium text-gray-500 uppercase tracking-wider">Miembro desde</label><p class="text-gray-800 font-medium mt-2">{{ $usuario->created_at->format('d \d\e F \d\e Y') }}</p></div>
                            <div class="mt-8 flex justify-end">
                                 {{-- 🔥 El botón ahora abre el modal con @click --}}
                                 <button @click="isModalOpen = true" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Editar Perfil
                                 </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Panel de Metas -->
            {{-- ... (Tu panel de metas, sin cambios, ya está perfecto) ... --}}
            @if($usuario->roles->contains('name', 'encargado'))
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg">
                        <div class="p-8 border-b border-gray-200">
                            <h3 class="text-2xl font-bold text-gray-800">Mi Progreso de Metas</h3>
                            <p class="text-gray-600 mt-1">Tu rendimiento mensual comparado con las metas establecidas.</p>
                        </div>
                        <div class="p-8 space-y-12">
                            @forelse ($metasPorAnio as $anio => $metas)
                                <div>
                                    <h4 class="text-xl font-bold text-gray-800 mb-4">Año: {{ $anio }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-10">
                                        @for ($mes = 1; $mes <= 12; $mes++)
                                            @php
                                                $metaMes = $metas->get($mes);
                                                $accionesMes = optional($accionesPorAnio->get($anio))->get($mes, 0);
                                                $metaCantidad = $metaMes ? $metaMes->cantidad : 0;
                                                $progreso = $metaCantidad > 0 ? min(100, ($accionesMes / $metaCantidad) * 100) : 0;
                                            @endphp
                                            <div>
                                                <div class="flex justify-between items-baseline mb-1">
                                                    <span class="font-bold text-gray-700">{{ Carbon\Carbon::create()->month($mes)->translatedFormat('F') }}</span>
                                                    @if($metaCantidad > 0)
                                                        <span class="text-sm font-semibold {{ $accionesMes >= $metaCantidad ? 'text-green-600' : 'text-gray-500' }}">
                                                            {{ $accionesMes }} / {{ $metaCantidad }} expedientes
                                                        </span>
                                                    @else
                                                        <span class="text-sm text-gray-400 italic">Sin meta</span>
                                                    @endif
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div class="h-2.5 rounded-full {{ $accionesMes >= $metaCantidad ? 'bg-green-500' : 'bg-indigo-600' }}" style="width: {{ $progreso }}%"></div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-500 py-8">Aún no se han establecido metas para ti.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>


    {{-- 🔥 INICIO: MODAL DE EDICIÓN 🔥 --}}
    <div 
        x-show="isModalOpen" 
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <!-- Fondo oscuro -->
        <div @click="isModalOpen = false" class="absolute inset-0 bg-gray-900 bg-opacity-75"></div>

        <!-- Contenedor del Modal -->
        <div 
            x-show="isModalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-auto overflow-y-auto" style="max-height: 90vh;"
        >
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 id="modal-title" class="text-2xl font-bold text-gray-800">Editar Perfil</h2>
                    <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                {{-- Errores de validación se muestran aquí DENTRO del modal --}}
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                        <h3 class="text-red-800 font-semibold mb-2">Por favor corrige los siguientes errores:</h3>
                        <ul class="list-disc list-inside text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                {{-- El formulario de edit.blade.php va aquí --}}
                <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')
                    
                    {{-- ... (Todos los campos del formulario: Nombre, Email, DNI, etc. idénticos a tu edit.blade.php) ... --}}
                     <div><label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nombre Completo</label><input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required></div>
                     <div><label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico</label><input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required></div>
                     <div><label for="dni" class="block text-sm font-semibold text-gray-700 mb-2">DNI</label><input type="text" name="dni" id="dni" value="{{ old('dni', auth()->user()->dni) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                     <div><label for="codigomodular" class="block text-sm font-semibold text-gray-700 mb-2">Código Modular</label><input type="text" name="codigomodular" id="codigomodular" value="{{ old('codigomodular', auth()->user()->codigomodular) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                     
                     <div class="border-t-2 border-gray-200 pt-6"><h3 class="text-lg font-semibold text-gray-800">Cambiar Contraseña</h3></div>
                     <div><label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Nueva Contraseña</label><input type="password" name="password" id="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                     <div><label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Contraseña</label><input type="password" name="password_confirmation" id="password_confirmation" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>

                    <!-- Botones de Acción -->
                    <div class="flex gap-4 pt-6 border-t-2 border-gray-200">
                        <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg">Guardar Cambios</button>
                        <button type="button" @click="isModalOpen = false" class="flex-1 px-6 py-3 bg-gray-200 text-gray-800 font-semibold rounded-lg">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection