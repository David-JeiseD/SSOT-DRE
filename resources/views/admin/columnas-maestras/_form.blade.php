<!-- Nombre a Mostrar -->
<div>
    <label for="{{ $formType }}_nombre_display" class="block text-sm font-medium text-gray-700">Nombre a Mostrar *</label>
    <input type="text" id="{{ $formType }}_nombre_display" name="nombre_display" value="{{ old('nombre_display', $columna->nombre_display ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    @error('nombre_display') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
</div>

<!-- El resto de los campos son idénticos al código anterior... -->
<!-- Nombre Normalizado -->
<div class="mt-4">
    <label for="{{ $formType }}_nombre_normalizado" class="block text-sm font-medium text-gray-700">Nombre Normalizado *</label>
    <input type="text" id="{{ $formType }}_nombre_normalizado" name="nombre_normalizado" value="{{ old('nombre_normalizado', $columna->nombre_normalizado ?? '') }}" required pattern="[a-z0-9_]+" title="Solo minúsculas, números y guion bajo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    <p class="text-xs text-gray-500 mt-1">Formato: `nombre_de_columna`. Sin espacios ni mayúsculas.</p>
    @error('nombre_normalizado') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
</div>

<!-- Descripción -->
<div class="mt-4">
    <label for="{{ $formType }}_descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
    <textarea id="{{ $formType }}_descripcion" name="descripcion" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('descripcion', $columna->descripcion ?? '') }}</textarea>
    @error('descripcion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
</div>

<!-- Es Fijo -->
<div class="mt-4">
    <label for="{{ $formType }}_es_fijo" class="block text-sm font-medium text-gray-700">¿Es Fijo? *</label>
    <select id="{{ $formType }}_es_fijo" name="es_fijo" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        <option value="0" @selected(old('es_fijo', $columna->es_fijo ?? '0') == '0')>No</option>
        <option value="1" @selected(old('es_fijo', $columna->es_fijo ?? '0') == '1')>Sí</option>
    </select>
    <p class="text-xs text-gray-500 mt-1">Las columnas fijas tienen protecciones adicionales contra eliminación.</p>
    @error('es_fijo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
</div>