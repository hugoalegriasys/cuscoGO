<!DOCTYPE html>
{{--
    Panel de telemetría IoT y resiliencia de aforo (CUS-06).

    Esta vista hace dos cosas a la vez: consulta el aforo de un sitio con
    sensor y permite forzar una caída del sensor para observar el fallback.
    Los modos "vivo" y "fallback" son una demo determinista del panel web;
    el endpoint /api/sitios/{sitio}/telemetria usa siempre el cliente HTTP
    real configurado en config/services.php.
--}}
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Telemetría IoT de aforo | cuscoGO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF6F0] font-sans text-[#2B3A32] antialiased">
    <header class="fixed top-0 z-50 w-full border-b border-[#E6DED2] bg-[#FAF6F0]/95 shadow-[0_1px_8px_rgba(15,61,46,0.05)] backdrop-blur-md">
        <div class="mx-auto flex h-20 max-w-275 items-center justify-between px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <span class="flex items-center justify-center text-[#0F3D2E]">
                    <svg class="h-6 w-6 fill-none stroke-current stroke-[1.5]" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 19h18M5 15h14M7 11h10M9 7h6M11 3h2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </span>
                <span class="font-headline-md text-headline-md tracking-tight text-[#0F3D2E]">Cusco<span class="font-normal italic text-[#A0401D]">Go</span></span>
            </a>
            <div class="inline-flex items-center gap-2 rounded-full border border-[#E6DED2] bg-white px-3 py-1.5 font-label-sm text-label-sm tracking-wide text-[#5A665D]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#0F3D2E] animate-pulse"></span>
                <span>TELEMETRÍA · RESILIENCIA DE AFORO</span>
            </div>
        </div>
    </header>

    <main class="min-h-[calc(100vh-80px)] w-full bg-[#FAF6F0] pt-20">
        <div class="mx-auto max-w-275 px-6 py-8">
            <header class="flex w-full flex-col items-start pb-6 pt-1">
                <p class="font-label-md text-label-md uppercase tracking-wider text-[#A0401D]">CUS-06 · Telemetría IoT y Resiliencia de Aforo</p>
                <h1 class="mt-3 max-w-3xl text-left font-headline-lg text-headline-lg tracking-tight text-[#0F3D2E]">Aforo en tiempo real, incluso cuando el sensor calla</h1>
                <p class="mt-2 max-w-2xl text-body-lg text-[#5A665D]">Elige un sitio con sensor IoT. Si el proveedor falla, el sistema muestra la última lectura registrada y la etiqueta como respaldo histórico.</p>
            </header>

            <div class="grid w-full grid-cols-1 items-stretch gap-8 lg:grid-cols-12">
                <div class="flex flex-col justify-between rounded-2xl border border-[#E6DED2] bg-white p-8 shadow-[0_4px_20px_-4px_rgba(15,61,46,0.06)] sm:p-10 lg:col-span-7">
                    <div>
                        <div class="mb-8">
                            <h2 class="font-headline-sm text-headline-sm text-[#0F3D2E]">Consultar telemetría</h2>
                            <p class="mt-1.5 text-body-md text-[#5A665D]">Consulta el sensor real o simula su caída para revisar el mecanismo de respaldo.</p>
                        </div>

                        {{--
                            Formulario GET: una consulta de telemetría no modifica estado,
                            por eso no necesita CSRF y sus parámetros quedan visibles en la URL.
                        --}}
                        <form id="form-telemetria" data-cy="form-telemetria" method="GET" action="{{ route('sitios.telemetria.index') }}" class="space-y-6">
                            <input type="hidden" name="consultar" value="1">

                            <div class="flex flex-col gap-2">
                                <label for="sitio_id" class="font-label-md text-label-md uppercase tracking-wider text-[#0F3D2E]">Sitio turístico con sensor</label>
                                <div class="relative">
                                    <select
                                        id="sitio_id"
                                        name="sitio_id"
                                        data-cy="selector-sitio"
                                        class="h-12 w-full cursor-pointer appearance-none rounded-lg border border-[#E6DED2] bg-[#FAF6F0] px-4 pr-10 text-body-md text-[#0F3D2E] transition-colors focus:border-[#0F3D2E] focus:outline-none"
                                    >
                                        @foreach ($sitios as $sitioDisponible)
                                            <option value="{{ $sitioDisponible->id }}" @selected($sitioId === (int) $sitioDisponible->id)>{{ $sitioDisponible->nombre }} · {{ $sitioDisponible->sensor_id }}</option>
                                        @endforeach
                                    </select>
                                    <span class="material-symbols-outlined pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-[20px] text-[#7A847C]">expand_more</span>
                                </div>
                                <p class="text-body-sm text-[#5A665D]">Cada sitio usa una clave de caché independiente durante 15 segundos.</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="modo" class="font-label-md text-label-md uppercase tracking-wider text-[#0F3D2E]">Modo de consulta</label>
                                <div class="relative">
                                    <select
                                        id="modo"
                                        name="modo"
                                        data-cy="modo"
                                        class="h-12 w-full cursor-pointer appearance-none rounded-lg border border-[#E6DED2] bg-[#FAF6F0] px-4 pr-10 text-body-md text-[#0F3D2E] transition-colors focus:border-[#0F3D2E] focus:outline-none"
                                    >
                                        <option value="vivo" @selected($modo === 'vivo' || $modo === '')>En vivo · demo (85%)</option>
                                        <option value="fallback" @selected($modo === 'fallback')>Fallback · simular caída (resiliencia)</option>
                                        <option value="real" @selected($modo === 'real')>En vivo · sensor real (URL configurada)</option>
                                    </select>
                                    <span class="material-symbols-outlined pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-[20px] text-[#7A847C]">expand_more</span>
                                </div>
                                <p class="text-body-sm text-[#5A665D]">En vivo consulta el sensor (demo o real); Fallback simula su caída para mostrar el respaldo histórico.</p>
                            </div>

                            <button
                                type="submit"
                                id="btn-consultar"
                                data-cy="btn-consultar"
                                class="flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-[#0F3D2E] px-6 py-3.5 font-label-lg text-label-lg uppercase tracking-wider text-white shadow-sm transition-all duration-200 hover:bg-[#1E4D3B] hover:shadow active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg id="spinner" class="hidden h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span id="texto-boton">Consultar aforo</span>
                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex w-full flex-col gap-6 lg:col-span-5">
                    @if ($error)
                        {{-- Estado controlado: el sensor falló y no hay lectura histórica, por eso HTTP 503. --}}
                        <section data-cy="alerta-503" role="alert" aria-live="assertive" class="flex h-full flex-col justify-between rounded-2xl border border-[#F0C2AE] bg-white p-7 shadow-[0_4px_20px_-4px_rgba(15,61,46,0.06)]">
                            <div class="mb-6 flex items-center justify-between gap-4 border-b border-[#F3E8DE] pb-4">
                                <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-[#8C2F12]">ESTADO DEL SERVICIO</span>
                                <span class="font-mono text-label-sm tabular-nums text-[#8C2F12]">HTTP {{ $error['codigo_http'] }}</span>
                            </div>

                            <div class="rounded-xl border border-[#F0C2AE] bg-[#FDF1EC] p-8 text-center">
                                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-full bg-white text-[#A0401D] shadow-sm">
                                    <span class="material-symbols-outlined text-[28px]">warning</span>
                                </div>
                                <span class="inline-flex items-center gap-2 rounded-full bg-[#8C2F12] px-4 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-white shadow-sm">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#FFD8C9]"></span>
                                    {{ $error['error'] }}
                                </span>
                                <h3 class="mt-4 font-headline-sm text-headline-sm text-[#8C2F12]">Sin telemetría disponible</h3>
                                <p class="mt-2 text-body-md leading-relaxed text-[#8C2F12]">{{ $error['message'] }}</p>
                            </div>

                            <p class="mt-4 text-center text-body-sm text-[#5A665D]">No existe una lectura previa en la base de datos para este sitio.</p>
                        </section>
                    @elseif ($resultado)
                        @php
                            // La etiqueta depende del origen, no del flag stale: el origen
                            // "cache" es válido y no degradado, mientras que "fallback_bd" sí lo es.
                            $etiquetaOrigen = match ($resultado['origen']) {
                                'iot_vivo' => 'En vivo (IoT)',
                                'cache' => 'Caché (15s)',
                                'fallback_bd' => 'Fallback · Respaldo histórico',
                                default => 'Origen desconocido',
                            };
                        @endphp

                        <section data-cy="resultado-telemetria" aria-live="polite" class="flex h-full flex-col justify-between rounded-2xl border border-[#E6DED2] bg-white p-7 shadow-[0_4px_20px_-4px_rgba(15,61,46,0.06)]">
                            <div class="mb-6 flex items-center justify-between gap-4 border-b border-[#F3E8DE] pb-4">
                                <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-[#5A665D]">AFORO DEL SITIO</span>
                                <span data-cy="sensor-id" class="font-mono text-label-sm tabular-nums text-[#5A665D]">{{ $resultado['sensor_id'] }}</span>
                            </div>

                            <div>
                                <h3 data-cy="sitio-nombre" class="font-headline-md text-headline-md text-[#0F3D2E]">{{ $resultado['sitio_nombre'] }}</h3>

                                {{-- Badge de origen: comunica si el dato es vivo, de caché o histórico. --}}
                                <span data-cy="badge-origen" class="mt-3 inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-white {{ $resultado['origen'] === 'fallback_bd' ? 'bg-[#8C2F12]' : 'bg-[#0F3D2E]' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $resultado['origen'] === 'fallback_bd' ? 'bg-[#FFD8C9]' : 'bg-[#BBEED5]' }}"></span>
                                    {{ $etiquetaOrigen }}
                                </span>

                                @if ($resultado['stale'])
                                    {{-- stale=true avisa de que el valor es real pero antiguo: no es un dato en vivo. --}}
                                    <div data-cy="badge-stale" role="status" class="mt-4 rounded-xl border border-[#F0C2AE] bg-[#FDF1EC] p-3 text-body-sm text-[#8C2F12]">
                                        Sensor sin señal · Mostrando última lectura registrada
                                    </div>
                                @endif

                                <div class="mt-6 flex items-end gap-2">
                                    <span data-cy="ocupacion" class="font-display text-display tabular-nums text-[#0F3D2E]">{{ $resultado['ocupacion'] }}%</span>
                                    <span class="pb-2 font-label-sm text-label-sm uppercase tracking-wider text-[#5A665D]">ocupación</span>
                                </div>

                                {{-- La barra usa el mismo valor que el texto para evitar contradicciones visuales. --}}
                                <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-[#EDE5DA]">
                                    <div
                                        data-cy="barra-aforo"
                                        role="progressbar"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="{{ (int) $resultado['ocupacion'] }}"
                                        class="h-full rounded-full transition-all duration-500 {{ $resultado['ocupacion'] > 80 ? 'bg-[#A0401D]' : 'bg-[#0F3D2E]' }}"
                                        style="width: {{ (int) $resultado['ocupacion'] }}%"
                                    ></div>
                                </div>

                                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-[#F3E8DE] pt-5 text-body-sm">
                                    <div>
                                        <dt class="font-label-sm text-label-sm uppercase tracking-wider text-[#7A847C]">Origen interno</dt>
                                        <dd class="mt-1 font-mono tabular-nums text-[#0F3D2E]">{{ $resultado['origen'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-label-sm text-label-sm uppercase tracking-wider text-[#7A847C]">Actualizado en</dt>
                                        <dd class="mt-1 font-mono tabular-nums text-[#0F3D2E]">{{ $resultado['actualizado_en'] }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </section>
                    @else
                        <section data-cy="estado-vacio" class="flex h-full flex-col items-center justify-center rounded-2xl border border-[#E6DED2] bg-white p-8 text-center shadow-[0_4px_20px_-4px_rgba(15,61,46,0.06)] sm:p-10">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#F3EEE7] text-[#0F3D2E]">
                                <span class="material-symbols-outlined text-[26px]">sensors</span>
                            </div>
                            <h3 class="mt-4 font-headline-sm text-headline-sm text-[#0F3D2E]">Sin consulta todavía</h3>
                            <p class="mx-auto mt-2 max-w-sm text-body-sm leading-relaxed text-[#5A665D]">Selecciona un sitio y presiona <strong class="font-semibold text-[#0F3D2E]">"Consultar aforo"</strong>. Aquí verás el porcentaje, la barra visual y el badge de origen del dato.</p>
                            <div class="mt-5 flex w-full items-center justify-center border-t border-[#F3E8DE] pt-5">
                                <span class="inline-flex items-center gap-2 rounded-full bg-[#F3EEE7] px-3.5 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-[#5A665D]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#7A847C]"></span>
                                    <span>En espera de consulta</span>
                                </span>
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <footer class="w-full border-t border-[#E6DED2] bg-[#F3EEE7] py-10">
        <div class="mx-auto flex max-w-275 flex-col items-center justify-between gap-4 px-6 md:flex-row">
            <p class="w-full text-center text-body-sm text-[#5A665D]">© {{ date('Y') }} cuscoGO · Datos de aforo con caché de 15 s y respaldo histórico</p>
        </div>
    </footer>

    <script>
        // Pequeño feedback visual mientras recarga la página: evita que el
        // usuario vuelva a pulsar y dispare consultas duplicadas.
        (() => {
            const formulario = document.getElementById('form-telemetria');
            const boton = document.getElementById('btn-consultar');
            const spinner = document.getElementById('spinner');
            const textoBoton = document.getElementById('texto-boton');

            if (formulario && boton && spinner && textoBoton) {
                formulario.addEventListener('submit', () => {
                    boton.disabled = true;
                    spinner.classList.remove('hidden');
                    textoBoton.textContent = 'Consultando...';
                });
            }
        })();
    </script>
</body>
</html>
