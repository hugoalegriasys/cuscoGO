@extends('layouts.app')

@section('titulo', 'Sitios Patrimoniales · cuscoGO')

@section('contenido')
    <header class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Sitios Patrimoniales</h1>
            <p class="mt-1 text-sm text-stone-500">
                Consulte el estado de operatividad de cada sitio según la hora actual y su horario de atención.
            </p>
        </div>
        <a
            href="{{ route('sitios.create') }}"
            class="inline-flex items-center gap-2 self-start rounded-md bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700/90 transition-colors"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Registrar sitio
        </a>
    </header>

    @if ($sitios->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-stone-300 bg-white p-10 text-center">
            <svg class="mx-auto h-12 w-12 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.113l.79-.94m0 0L9.75 3.75 12 3l2.25.75 3.28 2.56.79.94a3.004 3.004 0 0 1-.62 4.115"/>
            </svg>
            <h2 class="mt-3 text-lg font-semibold text-stone-700">Aún no hay sitios patrimoniales registrados</h2>
            <p class="mt-1 text-sm text-stone-500">Registre el primer sitio para habilitar la consulta de operatividad.</p>
            <a href="{{ route('sitios.create') }}" class="mt-4 inline-flex rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700/90 transition-colors">
                Registrar el primer sitio
            </a>
        </div>
    @else
        <p class="mb-4 text-sm text-stone-500">
            Se muestran <span class="font-semibold text-stone-700">{{ $sitios->count() }}</span>
            sitio(s) patrimonial(es). Estado actual evaluado a las
            <span class="font-semibold text-stone-700">{{ now()->format('H:i') }}</span>
            (hora del servidor).
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ($sitios as $sitio)
                @php
                    $estado = $estados[$sitio->getKey()] ?? null;

                    $clasesInsignia = match ($estado?->estado) {
                        'abierto' => 'bg-green-100 text-green-800 ring-1 ring-green-400',
                        'cerrado' => 'bg-red-100 text-red-800 ring-1 ring-red-400',
                        'inactivo' => 'bg-stone-200 text-stone-700 ring-1 ring-stone-400',
                        default => 'bg-amber-100 text-amber-800 ring-1 ring-amber-400',
                    };

                    $clasesPunto = match ($estado?->estado) {
                        'abierto' => 'bg-green-500',
                        'cerrado' => 'bg-red-500',
                        'inactivo' => 'bg-stone-500',
                        default => 'bg-amber-500',
                    };
                @endphp

                <article class="flex flex-col rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex-1 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold text-stone-900 truncate">{{ $sitio->nombre }}</h2>
                                <p class="mt-0.5 flex items-center gap-1 text-sm text-stone-500">
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                                    </svg>
                                    <span class="truncate">{{ $sitio->direccion }}</span>
                                </p>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $clasesInsignia }}">
                                <span class="h-2 w-2 rounded-full {{ $clasesPunto }}"></span>
                                {{ $estado?->estado ?? 'indeterminado' }}
                            </span>
                        </div>

                        @if ($sitio->descripcion)
                            <p class="mt-3 text-sm text-stone-600">{{ $sitio->descripcion }}</p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                            <span class="inline-flex items-center gap-1.5 text-stone-600">
                                <svg class="h-4 w-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                <span class="font-semibold">{{ $sitio->horarioAtencion() }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 {{ $sitio->activo ? 'text-green-700' : 'text-stone-500' }}">
                                <span class="h-2 w-2 rounded-full {{ $sitio->activo ? 'bg-green-500' : 'bg-stone-400' }}"></span>
                                {{ $sitio->activo ? 'Activo' : 'Temporalmente inactivo' }}
                            </span>
                        </div>

                        @if ($estado === null)
                            <p class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                El horario de atención registrado no es coherente, por lo que no fue posible evaluar el estado.
                            </p>
                        @endif
                    </div>

                    <div class="border-t border-stone-200 p-5">
                        @include('sitios.partials.verificar', ['sitio' => $sitio, 'estadoInicial' => $estado])
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-stone-100 bg-stone-50 px-5 py-3 text-sm">
                        <a href="{{ route('sitios.show', $sitio) }}" class="font-medium text-amber-800 hover:text-amber-900 hover:underline">Ver detalle</a>
                        <a href="{{ route('sitios.edit', $sitio) }}" class="font-medium text-stone-600 hover:text-stone-900 hover:underline">Editar</a>
                        <form method="POST" action="{{ route('sitios.destroy', $sitio) }}" onsubmit="return confirm('¿Eliminar el sitio patrimonial &quot;{{ addslashes($sitio->nombre) }}&quot;?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-red-700 hover:text-red-800 hover:underline">Eliminar</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
