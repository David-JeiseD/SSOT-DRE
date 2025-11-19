<div id="modalNuevaColumna" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="relative bg-white rounded-lg shadow-xl p-8 w-full max-w-lg">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Crear Nueva Columna Maestra</h3>
        <form action="{{ route('admin.columnas-maestras.store') }}" method="POST">
            @csrf
            @include('admin.columnas-maestras._form', ['formType' => 'create', 'columna' => null])
            <div class="mt-8 flex justify-end space-x-4">
                <button type="button" onclick="closeModal('modalNuevaColumna')" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar Columna</button>
            </div>
        </form>
        <button onclick="closeModal('modalNuevaColumna')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800">&times;</button>
    </div>
</div>