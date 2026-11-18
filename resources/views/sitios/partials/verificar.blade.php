@use('Illuminate\Support\Js')

{{--
    Widget de evaluación de estado de operatividad (CUS-03).
    Recibe:
      - $sitio       : App\Models\SitioPatrimonial
      - $estadoInicial : App\DTOs\EstadoOperatividadDTO|null (estado evaluado en el servidor)
    Permite consultar con la hora actual o con una hora personalizada vía AJAX.
--}}
<div
    x-data="{
        urlVerificacion: '{{ route('sitios.verificar', ['sitio' => $sitio->getKey()]) }}',
        token: '{{ csrf_token() }}',
        cargando: false,
        horaConsulta: '',
        resultado: {{ isset($estadoInicial) && $estadoInicial !== null ? Js::from($estadoInicial->toArray()) : 'null' }},
        error: null,
        async verificar() {
            this.cargando = true;
            this.error = null;

            try {
                const respuesta = await fetch(this.urlVerificacion, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.token,
                    },
                    body: JSON.stringify({
                        hora_consulta: this.horaConsulta.trim() === '' ? null : this.horaConsulta.trim(),
                    }),
                });

                const cuerpo = await respuesta.json();

                if (!respuesta.ok || !cuerpo.exitoso) {
                    const detalles = Array.isArray(cuerpo.errores) && cuerpo.errores.length > 0
                        ? ' ' + cuerpo.errores.join(' ')
                        : '';
                    this.error = cuerpo.mensaje + detalles;
                    this.resultado = null;
                } else {
                    this.resultado = cuerpo.datos;
                }
            } catch (falloRed) {
                this.error = 'No se pudo completar la verificación. Compruebe su conexión e inténtelo nuevamente.';
                this.resultado = null;
            } finally {
                this.cargando = false;
            }
        }
    }"
    class="rounded-lg border border-stone-200 bg-stone-50 p-4"
>
    <form class="flex flex-col sm:flex-row sm:items-end gap-3" x-on:submit.prevent="verificar()">
        <div class="flex-1">
            <label for="hora_consulta_{{ $sitio->getKey() }}" class="block text-xs font-semibold uppercase tracking-wide text-stone-500">
                Hora de consulta
            </label>
            <input
                id="hora_consulta_{{ $sitio->getKey() }}"
                type="time"
                step="60"
                x-model="horaConsulta"
                class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-600 focus:ring-amber-600"
                placeholder="HH:MM"
            >
            <p class="mt-1 text-xs text-stone-500">Déjelo vacío para consultar con la hora actual del sistema.</p>
        </div>
        <button
            type="submit"
            x-bind:disabled="cargando"
            x-bind:class="cargando ? 'opacity-70 cursor-not-allowed' : 'hover:bg-amber-700'"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors"
        >
            <svg x-show="cargando" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
            </svg>
            <span x-text="cargando ? 'Verificando...' : 'Verificar estado'"></span>
        </button>
    </form>

    <div x-show="error" x-cloak class="mt-4" role="alert">
        <div class="flex items-start gap-2 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <span x-text="error"></span>
        </div>
    </div>

    <div x-show="resultado" x-cloak class="mt-4">
        <template x-if="resultado">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide"
                        x-bind:class="{
                            'bg-green-100 text-green-800 ring-1 ring-green-400': resultado.estado === 'abierto',
                            'bg-red-100 text-red-800 ring-1 ring-red-400': resultado.estado === 'cerrado',
                            'bg-stone-200 text-stone-700 ring-1 ring-stone-400': resultado.estado === 'inactivo',
                        }"
                    >
                        <span
                            class="h-2 w-2 rounded-full"
                            x-bind:class="{
                                'bg-green-500': resultado.estado === 'abierto',
                                'bg-red-500': resultado.estado === 'cerrado',
                                'bg-stone-500': resultado.estado === 'inactivo',
                            }"
                        ></span>
                        <span x-text="resultado.estado"></span>
                    </span>
                    <span class="text-xs text-stone-500">
                        Consultado a las
                        <span class="font-semibold text-stone-700" x-text="resultado.hora_actual"></span>
                    </span>
                </div>

                <p class="mt-2 text-sm text-stone-700" x-text="resultado.mensaje"></p>

                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-stone-500">
                    <div>
                        <dt class="font-semibold uppercase tracking-wide">Apertura</dt>
                        <dd class="text-stone-700" x-text="resultado.hora_apertura"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold uppercase tracking-wide">Cierre</dt>
                        <dd class="text-stone-700" x-text="resultado.hora_cierre"></dd>
                    </div>
                </dl>
            </div>
        </template>
    </div>
</div>
