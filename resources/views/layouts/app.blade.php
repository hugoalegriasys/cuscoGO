<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'cuscoGO · Sitios Patrimoniales')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>[x-cloak]{display:none!important}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex flex-col bg-stone-100 text-stone-800 antialiased">

    <header class="bg-amber-800 text-amber-50 shadow-lg">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-600 text-lg font-bold">C</span>
                <span>
                    <span class="block text-lg font-semibold tracking-wide">cuscoGO</span>
                    <span class="block text-xs text-amber-200">Patrimonio vivo del Cusco</span>
                </span>
            </a>
            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ route('sitios.index') }}"
                   class="px-3 py-2 rounded-md font-medium hover:bg-amber-700 transition-colors">Sitios Patrimoniales</a>
                <a href="{{ route('sitios.create') }}"
                   class="px-3 py-2 rounded-md font-medium bg-amber-600 hover:bg-amber-500 transition-colors">Registrar Sitio</a>
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8">
        @if (session('exito'))
            <div x-data="{ visible: true }" x-show="visible" x-transition.leave.duration.500ms
                 class="mb-6 flex items-start justify-between gap-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                <p class="flex items-center gap-2 font-medium">
                    <svg class="h-5 w-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    {{ session('exito') }}
                </p>
                <button type="button" x-on:click="visible = false" class="text-green-600 hover:text-green-800" aria-label="Cerrar">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ visible: true }" x-show="visible"
                 class="mb-6 flex items-start justify-between gap-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="flex items-center gap-2 font-medium">
                    <svg class="h-5 w-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                    </svg>
                    {{ session('error') }}
                </p>
                <button type="button" x-on:click="visible = false" class="text-red-600 hover:text-red-800" aria-label="Cerrar">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        @yield('contenido')
    </main>

    <footer class="bg-stone-800 text-stone-400 text-xs">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p>cuscoGO · Plataforma turística del patrimonio del Cusco</p>
            <p>CUS-03 · Evaluación de Estado de Operatividad</p>
        </div>
    </footer>
</body>
</html>
