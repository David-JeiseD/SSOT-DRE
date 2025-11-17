<!-- Modal para Crear Nuevo Usuario -->
<div id="modalNuevoUsuario" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="text-xl font-semibold">Crear Nuevo Usuario</h3>
                <button type="button" onclick="closeModal('modalNuevoUsuario')" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="create_name" class="block text-sm font-medium mb-1">Nombre Completo *</label>
                        <input type="text" name="name" id="create_name" required value="{{ old('name') }}" class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="create_email" class="block text-sm font-medium mb-1">Correo Electrónico *</label>
                        <input type="email" name="email" id="create_email" required value="{{ old('email') }}" class="w-full border-gray-300 rounded-lg">
                    </div>
                </div>
                 <div>
                    <label for="create_dni" class="block text-sm font-medium mb-1">DNI (Opcional)</label>
                    <input type="text" name="dni" id="create_dni" maxlength="8" value="{{ old('dni') }}" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="create_rol" class="block text-sm font-medium mb-1">Rol del Usuario *</label>
                    <select name="rol" id="create_rol" required class="w-full border-gray-300 rounded-lg">
                        <option value="">Seleccione un rol...</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol }}" {{ old('rol') == $rol ? 'selected' : '' }}>{{ ucfirst($rol) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 rounded-r-lg">
                    <p class="text-sm">Se generará una contraseña segura automáticamente y se enviará al correo del nuevo usuario.</p>
                </div>
            </div>
            <div class="p-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('modalNuevoUsuario')" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>