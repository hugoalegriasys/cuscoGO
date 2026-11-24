<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Búsqueda de sitios por cercanía | CuscoGO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface text-body-md text-on-surface antialiased">
    <header class="fixed top-0 z-50 w-full border-b border-surface-container-highest bg-surface/90 shadow-[0_1px_8px_rgba(0,0,0,0.02)] backdrop-blur-md">
        <div class="mx-auto flex h-20 max-w-275 items-center justify-between px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <span class="flex items-center justify-center text-primary-container">
                    <svg class="h-6 w-6 fill-none stroke-current stroke-[1.5]" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 19h18M5 15h14M7 11h10M9 7h6M11 3h2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </span>
                <span class="font-headline-md text-headline-md tracking-tight text-primary">Cusco<span class="font-normal italic text-secondary">Go</span></span>
            </a>
            <div class="inline-flex items-center gap-2 rounded-full border border-surface-container-highest bg-surface-container-highest px-3 py-1.5 font-label-sm text-label-sm tracking-wide text-on-surface-variant">
                <span class="h-1.5 w-1.5 rounded-full bg-primary-container animate-pulse"></span>
                <span>GEOLOCALIZACIÓN · HAVERSINE</span>
            </div>
        </div>
    </header>

    <main class="min-h-[calc(100vh-80px)] w-full bg-surface pt-20">
        <div class="mx-auto max-w-275 px-6 py-8">
            <header class="flex w-full flex-col items-start pb-6 pt-1">
                <p class="font-label-md text-label-md uppercase tracking-wider text-secondary">CUS-05 · Búsqueda de Sitios por Cercanía (Geolocalización / GPS)</p>
                <h1 class="mt-3 max-w-3xl text-left font-headline-lg text-headline-lg tracking-tight text-on-surface">Descubre qué hay cerca de ti</h1>
                <p class="mt-2 max-w-2xl text-body-lg text-on-surface-variant">Indica tu ubicación y el radio de búsqueda para ordenar los sitios turísticos de menor a mayor distancia.</p>
            </header>

            <div class="grid w-full grid-cols-1 items-stretch gap-8 lg:grid-cols-12">
                <div class="flex flex-col justify-between rounded-2xl border border-surface-container-highest bg-surface-container-lowest p-8 shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)] sm:p-10 lg:col-span-7">
                    <div>
                        <div class="mb-8">
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">Buscar sitios cercanos</h2>
                            <p class="mt-1.5 text-body-md text-on-surface-variant">Usa tu GPS, elige un punto de referencia o escribe las coordenadas manualmente.</p>
                        </div>

                        @if ($errors->any())
                            <div role="alert" data-cy="alerta-error" class="mb-6 rounded-xl border border-[#F0C2AE] bg-[#FDF1EC] p-4 text-sm text-[#8C2F12]">
                                <p class="font-semibold">Revisa los datos ingresados:</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form id="form-cercania" data-cy="form-cercania" method="POST" action="{{ route('sitios.cercanos.buscar') }}" novalidate class="space-y-6">
                            @csrf

                            <div class="flex flex-col gap-2">
                                <span class="font-label-md text-label-md uppercase tracking-wider text-on-surface">Puntos de referencia</span>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        id="btn-preset-cusco"
                                        data-cy="btn-preset-cusco"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-surface-container-highest bg-surface-container-low px-4 py-2 font-label-md text-label-md text-on-surface transition-colors hover:bg-surface-container"
                                    >
                                        <span class="material-symbols-outlined text-[16px] text-primary">location_city</span>
                                        Plaza de Armas Cusco
                                    </button>
                                    <button
                                        type="button"
                                        id="btn-preset-ollantaytambo"
                                        data-cy="btn-preset-ollantaytambo"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-surface-container-highest bg-surface-container-low px-4 py-2 font-label-md text-label-md text-on-surface transition-colors hover:bg-surface-container"
                                    >
                                        <span class="material-symbols-outlined text-[16px] text-primary">account_balance</span>
                                        Ollantaytambo
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="latitud" class="font-label-md text-label-md uppercase tracking-wider text-on-surface">Latitud</label>
                                    <input
                                        type="number"
                                        id="latitud"
                                        name="latitud"
                                        data-cy="latitud"
                                        value="{{ old('latitud', $resultado['origen']['latitud'] ?? '-13.5167') }}"
                                        step="any"
                                        inputmode="decimal"
                                        class="h-12 w-full rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 text-body-md font-mono tabular-nums text-on-surface transition-colors focus:border-primary focus:outline-none"
                                    >
                                    <p class="text-body-sm text-on-surface-variant">Entre -90 y 90 grados.</p>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="longitud" class="font-label-md text-label-md uppercase tracking-wider text-on-surface">Longitud</label>
                                    <input
                                        type="number"
                                        id="longitud"
                                        name="longitud"
                                        data-cy="longitud"
                                        value="{{ old('longitud', $resultado['origen']['longitud'] ?? '-71.9781') }}"
                                        step="any"
                                        inputmode="decimal"
                                        class="h-12 w-full rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 text-body-md font-mono tabular-nums text-on-surface transition-colors focus:border-primary focus:outline-none"
                                    >
                                    <p class="text-body-sm text-on-surface-variant">Entre -180 y 180 grados.</p>
                                </div>
                            </div>

                            <button
                                type="button"
                                id="btn-gps"
                                data-cy="btn-gps"
                                class="flex h-12 w-full items-center justify-center gap-2 rounded-lg border border-surface-container-highest bg-surface-container-low px-4 font-label-md text-label-md uppercase tracking-wider text-on-surface transition-colors hover:bg-surface-container"
                            >
                                <span class="material-symbols-outlined text-[18px] text-primary">my_location</span>
                                Usar mi ubicación GPS
                            </button>
                            <p id="gps-estado" class="text-body-sm text-on-surface-variant" aria-live="polite">El GPS funciona mejor si permites el acceso a tu ubicación.</p>

                            <div class="flex flex-col gap-2">
                                <label for="radio_km" class="font-label-md text-label-md uppercase tracking-wider text-on-surface">Radio de búsqueda</label>
                                <div class="relative">
                                    <select
                                        id="radio_km"
                                        name="radio_km"
                                        data-cy="radio-km"
                                        class="h-12 w-full cursor-pointer appearance-none rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 pr-10 text-body-md text-on-surface transition-colors focus:border-primary focus:outline-none"
                                    >
                                        @foreach ([1, 5, 10, 50, 100] as $radio)
                                            <option value="{{ $radio }}" @selected((float) old('radio_km', $resultado['origen']['radio_km'] ?? 5) === (float) $radio)>{{ $radio }} km</option>
                                        @endforeach
                                    </select>
                                    <span class="material-symbols-outlined pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-[20px] text-outline">expand_more</span>
                                </div>
                                <p class="text-body-sm text-on-surface-variant">Solo se muestran sitios dentro de este radio.</p>
                            </div>

                            <button
                                type="submit"
                                id="btn-buscar-cercanos"
                                data-cy="btn-buscar-cercanos"
                                class="flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 font-label-lg text-label-lg uppercase tracking-wider text-on-primary shadow-sm transition-all duration-200 hover:bg-primary-container hover:shadow active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg id="spinner" class="hidden h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span id="texto-boton">Buscar sitios cercanos</span>
                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex w-full flex-col gap-6 lg:col-span-5">
                    @if ($errors->any())
                        <section class="flex h-full flex-col justify-between rounded-2xl border border-[#E6E1D6] bg-surface-container-lowest p-7 shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)]">
                            <div class="mb-6 flex items-center justify-between gap-4 border-b border-surface-container-low pb-4">
                                <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-[#57534E]">RESULTADO DE BÚSQUEDA</span>
                                <span class="font-mono text-label-sm tabular-nums text-on-surface-variant">Validación</span>
                            </div>

                            <div class="rounded-xl border border-[#F0C2AE] bg-[#FDF0EA] p-8 text-center">
                                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-full bg-white text-secondary shadow-sm">
                                    <span class="material-symbols-outlined text-[28px]">warning</span>
                                </div>
                                <span class="inline-flex items-center gap-2 rounded-full bg-secondary px-4 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-white shadow-sm">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#FFD8C9]"></span>
                                    ERROR DE VALIDACIÓN
                                </span>
                                <div class="mt-4 text-body-md leading-relaxed text-[#8C2F12]">
                                    <p class="font-semibold">No se pudo realizar la búsqueda:</p>
                                    <ul class="mt-2 list-inside list-disc space-y-1 text-left">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <p class="mt-4 text-center text-body-sm text-on-surface-variant">Corrige los datos del formulario e intenta nuevamente.</p>
                        </section>
                    @elseif ($resultado)
                        <section data-cy="resultados-cercania" class="flex h-full flex-col justify-between rounded-2xl border border-[#E6E1D6] bg-surface-container-lowest p-7 shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)]">
                            <div class="mb-6 flex items-center justify-between gap-4 border-b border-surface-container-low pb-4">
                                <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-[#57534E]">SITIOS POR CERCANÍA</span>
                                <span class="font-mono text-label-sm tabular-nums text-on-surface-variant">{{ $resultado['total'] }} en {{ number_format((float) $resultado['origen']['radio_km'], 2) }} km</span>
                            </div>

                            @if ($resultado['total'] > 0)
                                <div class="flex flex-col gap-3">
                                    @foreach ($resultado['data'] as $sitio)
                                        <article data-cy="item-sitio-cercano" class="flex items-center justify-between gap-4 rounded-xl border border-surface-container-highest bg-surface-container-low p-4">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-label-sm tabular-nums text-on-surface-variant">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                                    <h3 class="truncate font-headline-sm text-headline-sm text-on-surface">{{ $sitio['nombre'] }}</h3>
                                                </div>
                                                <p class="mt-1 truncate text-body-sm text-on-surface-variant">{{ $sitio['categoria'] ?? 'Sitio turístico' }} · {{ $sitio['ubicacion'] ?? 'Cusco' }}</p>
                                            </div>
                                            <span data-cy="badge-distancia" class="inline-flex shrink-0 items-center gap-1 rounded-full bg-primary-container px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-wider text-white">
                                                <span class="material-symbols-outlined text-[14px]">near_me</span>
                                                {{ number_format((float) $sitio['distancia_km'], 2) }} km
                                            </span>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div data-cy="estado-vacio" class="rounded-xl border border-surface-container-highest bg-surface-container-low p-8 text-center">
                                    <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-full bg-white text-secondary shadow-sm">
                                        <span class="material-symbols-outlined text-[28px]">explore_off</span>
                                    </div>
                                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Sin resultados en este radio</h3>
                                    <p class="mx-auto mt-2 max-w-xs text-body-sm leading-relaxed text-on-surface-variant">No hay sitios registrados dentro de {{ number_format((float) $resultado['origen']['radio_km'], 2) }} km del punto indicado. Prueba con un radio mayor.</p>
                                </div>
                            @endif

                            <p class="mt-4 text-center text-body-sm text-on-surface-variant">Origen: {{ number_format((float) $resultado['origen']['latitud'], 4) }}, {{ number_format((float) $resultado['origen']['longitud'], 4) }}</p>
                        </section>
                    @else
                        <section class="flex h-full flex-col items-center justify-center rounded-2xl border border-surface-container-highest bg-surface-container-lowest p-8 text-center shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)] sm:p-10">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-container-low text-primary">
                                <span class="material-symbols-outlined text-[26px]">near_me</span>
                            </div>
                            <h3 class="mt-4 font-headline-sm text-headline-sm text-on-surface">Sin búsqueda todavía</h3>
                            <p class="mx-auto mt-2 max-w-sm text-body-sm leading-relaxed text-on-surface-variant">Completa tu ubicación y presiona <strong class="font-semibold text-on-surface">"Buscar sitios cercanos"</strong>. Aquí verás el listado ordenado por distancia con su badge en kilómetros.</p>
                            <div class="mt-5 flex w-full items-center justify-center border-t border-surface-container-low pt-5">
                                <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3.5 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">
                                    <span class="h-1.5 w-1.5 rounded-full bg-outline"></span>
                                    <span>En espera de ubicación</span>
                                </span>
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <footer class="w-full border-t border-surface-container-highest bg-surface-container-low py-10">
        <div class="mx-auto flex max-w-275 flex-col items-center justify-between gap-4 px-6 md:flex-row">
            <p class="w-full text-center text-body-sm text-on-surface-variant">© {{ date('Y') }} CuscoGo</p>
        </div>
    </footer>

    <script>
        (() => {
            const inputLatitud = document.getElementById('latitud');
            const inputLongitud = document.getElementById('longitud');
            const estadoGps = document.getElementById('gps-estado');

            const establecerOrigen = (latitud, longitud, mensaje) => {
                inputLatitud.value = latitud;
                inputLongitud.value = longitud;
                if (estadoGps) {
                    estadoGps.textContent = mensaje;
                }
            };

            const btnCusco = document.getElementById('btn-preset-cusco');
            const btnOllantaytambo = document.getElementById('btn-preset-ollantaytambo');

            if (btnCusco) {
                btnCusco.addEventListener('click', () => {
                    establecerOrigen('-13.5167', '-71.9781', 'Origen: Plaza de Armas Cusco.');
                });
            }

            if (btnOllantaytambo) {
                btnOllantaytambo.addEventListener('click', () => {
                    establecerOrigen('-13.2584', '-72.2633', 'Origen: Ollantaytambo.');
                });
            }

            const btnGps = document.getElementById('btn-gps');

            if (btnGps) {
                btnGps.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        if (estadoGps) {
                            estadoGps.textContent = 'Tu navegador no admite geolocalización.';
                        }
                        return;
                    }

                    if (estadoGps) {
                        estadoGps.textContent = 'Obteniendo tu ubicación...';
                    }

                    navigator.geolocation.getCurrentPosition(
                        (posicion) => {
                            establecerOrigen(
                                posicion.coords.latitude.toFixed(7),
                                posicion.coords.longitude.toFixed(7),
                                'Ubicación GPS obtenida correctamente.'
                            );
                        },
                        () => {
                            if (estadoGps) {
                                estadoGps.textContent = 'No se pudo obtener tu ubicación. Revisa los permisos del navegador.';
                            }
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                });
            }

            const formulario = document.getElementById('form-cercania');
            const boton = document.getElementById('btn-buscar-cercanos');
            const spinner = document.getElementById('spinner');
            const textoBoton = document.getElementById('texto-boton');

            if (formulario && boton && spinner && textoBoton) {
                formulario.addEventListener('submit', () => {
                    boton.disabled = true;
                    spinner.classList.remove('hidden');
                    textoBoton.textContent = 'Buscando...';
                });
            }
        })();
    </script>
</body>
</html>
