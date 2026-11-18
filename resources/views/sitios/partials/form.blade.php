{{--
    Formulario de registro/edición de un sitio patrimonial (CUS-03).
    Recibe:
      - $accion  : string  (URL destino del formulario)
      - $metodo  : string  ('POST' para crear, 'PUT' para actualizar)
      - $sitio   : App\Models\SitioPatrimonial|null (null en registro)
--}}
<form
    method="POST"
    action="{{ $accion }}"
    x-data="{ enviando: false }"
    x-on:submit="if (enviando) return; enviando = true"
    class="space-y-6"
    novalidate
>
    @csrf
    @if (($metodo ?? 'POST') !== 'POST')
        @method(strtoupper($metodo))
    @endif

    <div>
        <label for="nombre" class="block text-sm font-semibold text-stone-700">Nombre del sitio patrimonial <span class="text-red-600">*</span></label>
        <input
            id="nombre"
            type="text"
            name="nombre"
            value="{{ old('nombre', $sitio->nombre ?? '') }}"
            maxlength="150"
            required
            placeholder="Ejemplo: Qorikancha"
            class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-600 focus:ring-amber-600 @error('nombre') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
        >
        @error('nombre')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @else
            <p class="mt-1 text-xs text-stone-500">Entre 3 y 150 caracteres. Debe ser único en el sistema.</p>
        @enderror
    </div>

    <div>
        <label for="direccion" class="block text-sm font-semibold text-stone-700">Dirección <span class="text-red-600">*</span></label>
        <input
            id="direccion"
            type="text"
            name="direccion"
            value="{{ old('direccion', $sitio->direccion ?? '') }}"
            maxlength="255"
            required
            placeholder="Ejemplo: Calle Santo Domingo, Cusco"
            class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-600 focus:ring-amber-600 @error('direccion') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
        >
        @error('direccion')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @else
            <p class="mt-1 text-xs text-stone-500">Entre 5 y 255 caracteres.</p>
        @enderror
    </div>

    <div>
        <label for="descripcion" class="block text-sm font-semibold text-stone-700">Descripción</label>
        <textarea
            id="descripcion"
            name="descripcion"
            rows="4"
            maxlength="1000"
            placeholder="Breve descripción del sitio patrimonial (opcional)"
            class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-600 focus:ring-amber-600 @error('descripcion') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
        >{{ old('descripcion', $sitio->descripcion ?? '') }}</textarea>
        @error('descripcion')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @else
            <p class="mt-1 text-xs text-stone-500">Hasta 1000 caracteres.</p>
        @enderror
    </div>

    <fieldset class="rounded-lg border border-stone-200 bg-white p-4">
        <legend class="px-2 text-sm font-semibold text-stone-700">Horario de atención <span class="text-red-600">*</span></legend>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="hora_apertura" class="block text-sm font-medium text-stone-700">Hora de apertura <span class="text-red-600">*</span></label>
                <input
                    id="hora_apertura"
                    type="time"
                    step="60"
                    name="hora_apertura"
                    value="{{ old('hora_apertura', $sitio->hora_apertura ?? '09:00') }}"
                    required
                    class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-600 focus:ring-amber-600 @error('hora_apertura') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                @error('hora_apertura')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="hora_cierre" class="block text-sm font-medium text-stone-700">Hora de cierre <span class="text-red-600">*</span></label>
                <input
                    id="hora_cierre"
                    type="time"
                    step="60"
                    name="hora_cierre"
                    value="{{ old('hora_cierre', $sitio->hora_cierre ?? '17:00') }}"
                    required
                    class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-600 focus:ring-amber-600 @error('hora_cierre') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                @error('hora_cierre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @else
                    <p class="mt-1 text-xs text-stone-500">Debe ser posterior a la hora de apertura.</p>
                @enderror
            </div>
        </div>
    </fieldset>

    <div class="flex items-start gap-3 rounded-lg border border-stone-200 bg-white p-4">
        <input
            id="activo"
            type="checkbox"
            name="activo"
            value="1"
            {{ old('activo', isset($sitio) ? $sitio->activo : true) ? 'checked' : '' }}
            class="mt-0.5 h-4 w-4 rounded border-stone-300 text-amber-700 focus:ring-amber-600"
        >
        <div>
            <label for="activo" class="text-sm font-semibold text-stone-700">Sitio activo</label>
            <p class="text-xs text-stone-500">Los sitios inactivos se reportan como no operativos para los turistas.</p>
            @error('activo')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <a
            href="{{ route('sitios.index') }}"
            class="inline-flex items-center justify-center rounded-md border border-stone-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 shadow-sm hover:bg-stone-50 transition-colors"
        >
            Cancelar
        </a>
        <button
            type="submit"
            x-bind:disabled="enviando"
            x-bind:class="enviando ? 'opacity-70 cursor-not-allowed' : 'hover:bg-amber-700'"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-amber-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors"
        >
            <svg x-show="enviando" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
            </svg>
            <span x-text="enviando ? 'Guardando...' : '{{ $etiquetaBoton ?? 'Registrar sitio' }}'"></span>
        </button>
    </div>
</form>
