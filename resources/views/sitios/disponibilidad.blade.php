<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Disponibilidad de sitios turísticos | CuscoGO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface text-body-md text-on-surface antialiased" data-hora-servidor="{{ $horaActual }}:00">
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
                <span>HORA EN CUSCO · <span id="reloj" class="font-mono tabular-nums">{{ $horaActual }}:00</span> (America/Lima)</span>
            </div>
        </div>
    </header>

    <main class="min-h-[calc(100vh-80px)] w-full bg-surface pt-20">
        <div class="mx-auto max-w-275 px-6 py-8">
            <header class="flex w-full flex-col items-start pb-6 pt-1">
                <p class="font-label-md text-label-md uppercase tracking-wider text-secondary">CUS-03 · Evaluación de estado de operatividad</p>
                <h1 class="mt-3 max-w-3xl text-left font-headline-lg text-headline-lg tracking-tight text-on-surface">Disponibilidad de sitios turísticos</h1>
                <p class="mt-2 max-w-2xl font-body-lg text-body-lg text-on-surface-variant">Consulta si un destino está abierto o cerrado según su horario de atención.</p>
            </header>

            <div class="grid w-full grid-cols-1 items-stretch gap-8 lg:grid-cols-12">
                <div class="flex flex-col justify-between rounded-2xl border border-surface-container-highest bg-surface-container-lowest p-8 shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)] sm:p-10 lg:col-span-7">
                    <div>
                        <div class="mb-8">
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">Consultar disponibilidad</h2>
                            <p class="mt-1.5 font-body-md text-body-md text-on-surface-variant">Selecciona un sitio registrado en el catastro o ingresa un horario manual para evaluar la operatividad en tiempo real.</p>
                        </div>

                        @if ($errors->any())
                            <div role="alert" class="mb-6 rounded-xl border border-[#F0C2AE] bg-[#FDF1EC] p-4 text-sm text-[#8C2F12]">
                                <p class="font-semibold">Revisa los datos ingresados:</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form id="form-disponibilidad" data-cy="form-disponibilidad" method="POST" action="{{ route('sitios.disponibilidad.evaluar') }}" class="space-y-6">
                            @csrf

                            <div class="flex flex-col gap-2">
                                <label for="sitio_id" class="font-label-md text-label-md uppercase tracking-wider text-on-surface">Sitio turístico</label>
                                <div class="relative">
                                    <select
                                        id="sitio_id"
                                        name="sitio_id"
                                        data-cy="sitio"
                                        class="h-12 w-full cursor-pointer appearance-none rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 pr-10 font-body-md text-body-md text-on-surface transition-colors focus:border-primary focus:outline-none"
                                    >
                                        <option value="">Evaluar con horario manual</option>
                                        @foreach ($sitios as $sitio)
                                            @php
                                                $horarioDelDia = $sitio->horariosActivos->firstWhere('dia_semana', $diaSemanaActual);
                                            @endphp
                                            <option value="{{ $sitio->id }}" @selected((string) old('sitio_id') === (string) $sitio->id)>
                                                {{ $sitio->nombre }}@if ($horarioDelDia) · {{ $horarioDelDia->apertura }} a {{ $horarioDelDia->cierre }}@else · sin horario hoy @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="material-symbols-outlined pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-[20px] text-outline">expand_more</span>
                                </div>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">Si eliges un sitio, se usará el horario registrado para la fecha elegida.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="fecha" class="font-label-md text-label-md uppercase tracking-wider text-on-surface">Fecha de la visita</label>
                                    <input
                                        type="date"
                                        id="fecha"
                                        name="fecha"
                                        data-cy="fecha"
                                        value="{{ old('fecha', $fechaActual) }}"
                                        class="h-12 w-full rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 text-body-md font-mono tabular-nums text-on-surface transition-colors focus:border-primary focus:outline-none"
                                    >
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">Opcional. Se usa para determinar el día de la semana.</p>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="hora_actual" class="font-label-md text-label-md uppercase tracking-wider text-on-surface">Hora a evaluar</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="time"
                                            id="hora_actual"
                                            name="hora_actual"
                                            data-cy="hora-actual"
                                            value="{{ old('hora_actual', $horaActual) }}"
                                            step="60"
                                            required
                                            class="h-12 w-full rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 text-body-md font-mono tabular-nums text-on-surface transition-colors focus:border-primary focus:outline-none"
                                        >
                                        <button
                                            type="button"
                                            id="btn-ahora"
                                            data-cy="btn-ahora"
                                            class="flex h-12 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-surface-container-highest bg-surface-container-low px-4 font-label-md text-label-md tracking-wider text-on-surface transition-colors hover:bg-surface-container"
                                        >
                                            <span class="material-symbols-outlined text-[16px] text-primary">schedule</span>
                                            <span>Ahora</span>
                                        </button>
                                    </div>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">Formato de 24 horas (00:00 a 23:59).</p>
                                </div>
                            </div>

                            <div id="campos-manuales" class="rounded-xl border border-surface-container-highest bg-surface-container-low p-5 transition-all duration-300">
                                <div class="mb-1.5 flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-2 font-label-lg text-label-lg text-on-surface">
                                        <span class="material-symbols-outlined text-[18px] text-primary">tune</span>
                                        Horario de referencia
                                    </span>
                                    <span id="regime-tag" class="rounded-full bg-surface-container px-2.5 py-0.5 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Manual</span>
                                </div>
                                <p class="mb-4 font-body-sm text-body-sm text-on-surface-variant">La apertura debe ser anterior al cierre. La hora de cierre se considera inclusiva.</p>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label for="apertura" class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">Apertura</label>
                                        <input
                                            type="time"
                                            id="apertura"
                                            name="apertura"
                                            data-cy="apertura"
                                            value="{{ old('apertura', '09:00') }}"
                                            step="60"
                                            class="h-11 w-full rounded-lg border border-surface-container-highest bg-surface-container-lowest px-3 text-center text-body-md font-mono tabular-nums text-on-surface transition-colors focus:border-primary focus:outline-none"
                                        >
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label for="cierre" class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">Cierre</label>
                                        <input
                                            type="time"
                                            id="cierre"
                                            name="cierre"
                                            data-cy="cierre"
                                            value="{{ old('cierre', '17:00') }}"
                                            step="60"
                                            class="h-11 w-full rounded-lg border border-surface-container-highest bg-surface-container-lowest px-3 text-center text-body-md font-mono tabular-nums text-on-surface transition-colors focus:border-primary focus:outline-none"
                                        >
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                id="btn-evaluar"
                                data-cy="btn-evaluar"
                                class="flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 font-label-lg text-label-lg uppercase tracking-wider text-on-primary shadow-sm transition-all duration-200 hover:bg-primary-container hover:shadow active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg id="spinner" class="hidden h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span id="texto-boton">Evaluar estado</span>
                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex w-full flex-col gap-6 lg:col-span-5">
                    @if ($errors->any())
                        <section data-cy="resultado-container" class="flex h-full flex-col justify-between rounded-2xl border border-[#E6E1D6] bg-surface-container-lowest p-7 shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)]">
                            <div class="mb-6 flex items-center justify-between gap-4 border-b border-surface-container-low pb-4">
                                <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-[#57534E]">RESULTADO DE CONSULTA</span>
                                <span class="font-mono text-label-sm tabular-nums text-on-surface-variant">Validación</span>
                            </div>

                            <div class="rounded-xl border border-[#F0C2AE] bg-[#FDF0EA] p-8 text-center">
                                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-full bg-white text-secondary shadow-sm">
                                    <span class="material-symbols-outlined text-[28px]">warning</span>
                                </div>
                                <span data-cy="badge-resultado" class="inline-flex items-center gap-2 rounded-full bg-secondary px-4 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-white shadow-sm">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#FFD8C9]"></span>
                                    ERROR DE VALIDACIÓN
                                </span>
                                <div data-cy="mensaje-resultado" class="mt-4 font-body-md text-body-md leading-relaxed text-[#8C2F12]">
                                    <p class="font-semibold">No se pudo evaluar la operatividad:</p>
                                    <ul class="mt-2 list-inside list-disc space-y-1 text-left">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <p class="mt-4 text-center font-body-sm text-body-sm text-on-surface-variant">Corrige los datos del formulario e intenta nuevamente.</p>
                        </section>
                    @elseif (session('resultado'))
                        @php
                            $abierto = (bool) ($resultado['abierto'] ?? false);
                            $fechaResultado = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', (string) ($resultado['fecha'] ?? ''));
                            $fechaMostrar = $fechaResultado ? $fechaResultado->format('d/m/Y') : (string) ($resultado['fecha'] ?? '');
                        @endphp

                        <section data-cy="resultado-container" class="flex h-full flex-col justify-between rounded-2xl border border-[#E6E1D6] bg-surface-container-lowest p-7 shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)]">
                            <div class="mb-6 flex items-center justify-between gap-4 border-b border-surface-container-low pb-4">
                                <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-[#57534E]">RESULTADO DE CONSULTA</span>
                                <span class="font-mono text-label-sm tabular-nums text-on-surface-variant">{{ $resultado['dia_semana_nombre'] }} · {{ $fechaMostrar }}</span>
                            </div>

                            <div class="mb-6 rounded-xl border p-8 text-center {{ $abierto ? 'border-[#A7F3D0] bg-[#ECFDF5]' : 'border-[#F0C2AE] bg-[#FDF0EA]' }}">
                                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm {{ $abierto ? 'text-primary-container' : 'text-secondary' }}">
                                    <span class="material-symbols-outlined text-[28px]">{{ $abierto ? 'check_circle' : 'door_front' }}</span>
                                </div>
                                <span data-cy="badge-resultado" class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-white shadow-sm {{ $abierto ? 'bg-primary-container' : 'bg-secondary' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $abierto ? 'animate-ping bg-[#A7F3D0]' : 'bg-[#FFD8C9]' }}"></span>
                                    {{ $resultado['estado'] }}
                                </span>
                                <p data-cy="mensaje-resultado" class="mx-auto mt-3 max-w-sm font-body-md text-body-md leading-relaxed {{ $abierto ? 'text-primary-container' : 'text-[#8C2F12]' }}">{{ $resultado['mensaje'] }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 text-left font-body-sm sm:grid-cols-2">
                                <div class="flex flex-col rounded-xl border border-surface-container-highest bg-surface-container-low p-4">
                                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">SITIO</span>
                                    <span class="mt-1 font-body-md text-body-md font-semibold text-on-surface">{{ $resultado['sitio'] ?? 'Horario manual' }}</span>
                                </div>
                                <div class="flex flex-col rounded-xl border border-surface-container-highest bg-surface-container-low p-4">
                                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">HORARIO DE ATENCIÓN</span>
                                    <span class="mt-1 text-body-md font-semibold font-mono tabular-nums text-on-surface">{{ $resultado['horario'] }}</span>
                                </div>
                                <div class="flex flex-col rounded-xl border border-surface-container-highest bg-surface-container-low p-4">
                                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">HORA EVALUADA</span>
                                    <span class="mt-1 text-body-md font-semibold font-mono tabular-nums text-on-surface">{{ $resultado['hora_actual'] }}</span>
                                </div>
                                <div class="flex flex-col rounded-xl border border-surface-container-highest bg-surface-container-low p-4">
                                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">FECHA Y DÍA</span>
                                    <span class="mt-1 font-body-md text-body-md font-semibold text-on-surface">{{ $resultado['dia_semana_nombre'] }}, {{ $fechaMostrar }}</span>
                                </div>
                            </div>
                        </section>
                    @else
                        <section class="flex h-full flex-col items-center justify-center rounded-2xl border border-surface-container-highest bg-surface-container-lowest p-8 text-center shadow-[0_4px_20px_-4px_rgba(30,27,25,0.03)] sm:p-10">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-container-low text-primary">
                                <span class="material-symbols-outlined text-[26px]">history_toggle_off</span>
                            </div>
                            <h3 class="mt-4 font-headline-sm text-headline-sm text-on-surface">Sin evaluación todavía</h3>
                            <p class="mx-auto mt-2 max-w-sm font-body-sm text-body-sm leading-relaxed text-on-surface-variant">Completa los parámetros y presiona <strong class="font-semibold text-on-surface">"Evaluar estado"</strong>. Aquí verás si el sitio se encuentra abierto o cerrado, con el desglose horario y el margen de visita.</p>
                            <div class="mt-5 flex w-full items-center justify-center border-t border-surface-container-low pt-5">
                                <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3.5 py-1.5 font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">
                                    <span class="h-1.5 w-1.5 rounded-full bg-outline"></span>
                                    <span>En espera de consulta</span>
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
            <p class="w-full text-center font-body-sm text-body-sm text-on-surface-variant">© {{ date('Y') }} CuscoGo</p>
        </div>
    </footer>

    <script>
        (() => {
            const cuerpo = document.body;
            const reloj = document.getElementById('reloj');
            const partesIniciales = (cuerpo.dataset.horaServidor || '00:00:00').split(':').map(Number);

            let horas = Number.isFinite(partesIniciales[0]) ? partesIniciales[0] : 0;
            let minutos = Number.isFinite(partesIniciales[1]) ? partesIniciales[1] : 0;
            let segundos = Number.isFinite(partesIniciales[2]) ? partesIniciales[2] : 0;

            const pad = (valor) => String(valor).padStart(2, '0');

            const pintarReloj = () => {
                if (reloj) {
                    reloj.textContent = `${pad(horas)}:${pad(minutos)}:${pad(segundos)}`;
                }
            };

            pintarReloj();

            window.setInterval(() => {
                segundos += 1;

                if (segundos >= 60) {
                    segundos = 0;
                    minutos += 1;
                }

                if (minutos >= 60) {
                    minutos = 0;
                    horas = (horas + 1) % 24;
                }

                pintarReloj();
            }, 1000);

            const selectSitio = document.getElementById('sitio_id');
            const camposManuales = document.getElementById('campos-manuales');
            const inputApertura = document.getElementById('apertura');
            const inputCierre = document.getElementById('cierre');
            const inputHora = document.getElementById('hora_actual');
            const btnAhora = document.getElementById('btn-ahora');
            const regimeTag = document.getElementById('regime-tag');

            const alternarModo = () => {
                if (!selectSitio || !camposManuales || !inputApertura || !inputCierre) {
                    return;
                }

                const modoManual = selectSitio.value === '';

                camposManuales.classList.toggle('hidden', !modoManual);
                inputApertura.disabled = !modoManual;
                inputCierre.disabled = !modoManual;
                inputApertura.required = modoManual;
                inputCierre.required = modoManual;

                if (regimeTag) {
                    regimeTag.textContent = modoManual ? 'Manual' : 'Oficial';
                    regimeTag.classList.toggle('bg-primary-fixed', !modoManual);
                    regimeTag.classList.toggle('text-primary', !modoManual);
                    regimeTag.classList.toggle('bg-surface-container', modoManual);
                    regimeTag.classList.toggle('text-on-surface-variant', modoManual);
                }
            };

            if (selectSitio) {
                selectSitio.addEventListener('change', alternarModo);
                alternarModo();
            }

            if (btnAhora && inputHora) {
                btnAhora.addEventListener('click', () => {
                    inputHora.value = `${pad(horas)}:${pad(minutos)}`;
                });
            }

            const formulario = document.getElementById('form-disponibilidad');
            const boton = document.getElementById('btn-evaluar');
            const spinner = document.getElementById('spinner');
            const textoBoton = document.getElementById('texto-boton');

            if (formulario && boton && spinner && textoBoton) {
                formulario.addEventListener('submit', () => {
                    boton.disabled = true;
                    spinner.classList.remove('hidden');
                    textoBoton.textContent = 'Evaluando...';
                });
            }
        })();
    </script>
</body>
</html>
