<!-- Modal para Editar Usuario -->
<div id="modalEditarUsuario" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4">
        <form id="formEditarUsuario" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="text-xl font-semibold">Editar Usuario</h3>
                <button type="button" onclick="closeModal('modalEditarUsuario')" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_name" class="block text-sm font-medium mb-1">Nombre Completo *</label>
                        <input type="text" name="name" id="edit_name" required class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="edit_email" class="block text-sm font-medium mb-1">Correo Electrónico *</label>
                        <input type="email" name="email" id="edit_email" required class="w-full border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label for="edit_dni" class="block text-sm font-medium mb-1">DNI (Opcional)</label>
                    <input type="text" name="dni" id="edit_dni" maxlength="8" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="edit_rol" class="block text-sm font-medium mb-1">Rol del Usuario *</label>
                    <select name="rol" id="edit_rol" required class="w-full border-gray-300 rounded-lg">
                        @foreach($roles as $rol)
                            <option value="{{ $rol }}">{{ ucfirst($rol) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded-r-lg">
                    <p class="text-sm font-bold mb-2">Cambiar Contraseña (Opcional)</p>
                    <div class="space-y-2">
                        <input type="password" name="password" placeholder="Nueva contraseña" class="w-full border-gray-300 rounded-lg text-sm">
                        <input type="password" name="password_confirmation" placeholder="Confirmar nueva contraseña" class="w-full border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('modalEditarUsuario')" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Actualizar Usuario</button>
            </div>
        </form>
    </div>
</div>