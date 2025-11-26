<!-- resources/views/admin/users/partials/dashboard-reporte.blade.php -->

<div class="mt-12">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Reporte de Actividad</h2>

    <!-- KPIs Globales -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Tarjetas de estadísticas globales, usando la variable $stats --}}
        <div class="bg-white rounded-xl shadow p-6"><p class="text-sm text-gray-500">Usuarios Totales</p><p class="text-3xl font-bold">{{ $stats['totalUsuarios'] }}</p></div>
        <div class="bg-white rounded-xl shadow p-6"><p class="text-sm text-gray-500">Acciones Totales</p><p class="text-3xl font-bold">{{ $stats['totalAcciones'] }}</p></div>
        <div class="bg-white rounded-xl shadow p-6"><p class="text-sm text-gray-500">Usuario Más Activo</p><p class="text-xl font-bold text-indigo-600 truncate">{{ optional($stats['usuarioMasActivo'])->name ?? 'N/A' }}</p></div>
        <div class="bg-white rounded-xl shadow p-6"><p class="text-sm text-gray-500">Última Actividad</p><p class="text-xl font-bold">{{ optional($stats['ultimaActividad'])->created_at ?? 'N/A' }}</p></div>
    </div>

    <!-- Tarjetas de Usuarios Individuales -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($usuariosParaReporte as $usuario)
            <div class="bg-white rounded-xl shadow-lg p-6 border-t-4 border-indigo-500 flex flex-col justify-between">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                            <span class="text-gray-600 font-bold text-xl">{{ substr($usuario->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">{{ $usuario->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $usuario->email }}</p>
                        </div>
                    </div>
                    @if($usuario->roles->isNotEmpty())
                        <span class="px-2 py-1 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full">{{ $usuario->roles->first()->name }}</span>
                    @endif
                    <div class="mt-6 text-right">
                        <a href="{{ route('admin.users.detalle', $usuario) }}" class="text-sm text-indigo-600 font-semibold hover:underline">
                            Ver historial completo &rarr;
                        </a>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-4 text-center border-t pt-4">
                        <div>
                            <p class="text-2xl font-bold text-blue-600">{{ $usuario->total_subidas }}</p>
                            <p class="text-xs text-gray-500">Generados</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-yellow-600">{{ $usuario->total_ediciones }}</p>
                            <p class="text-xs text-gray-500">Editados</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600">{{ $usuario->total_eliminaciones }}</p>
                            <p class="text-xs text-gray-500">Eliminados</p>
                        </div>
                    </div>
                </div>
                {{-- (Opcional) Puedes añadir un botón para ver un detalle más completo en el futuro --}}
                {{-- <div class="mt-6 text-right">
                    <a href="#" class="text-sm text-indigo-600 font-semibold">Ver historial completo &rarr;</a>
                </div> --}}
            </div>
        @empty
            <p class="col-span-full text-center text-gray-500">No hay usuarios para mostrar en el reporte.</p>
        @endforelse
    </div>
</div>