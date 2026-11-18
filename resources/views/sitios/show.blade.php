@extends('layouts.app')

@section('titulo', "{$sitio->nombre} · cuscoGO")

@section('contenido')
    <div class="max-w-2xl mx-auto">
        <nav class="mb-4 text-sm text-stone-500" aria-label="Ruta de navegación">
            <a href="{{ route('sitios.index') }}" class="hover:text-amber-700 font-medium">Sitios Patrimoniales</a>
            <span class="mx-2" aria-hidden="true">/</span>
            <span class="text-stone-700" aria-current="page">{{ $sitio->nombre }}</span>
        </nav>

        @php
            $clasesInsignia = match ($estado?->estado) {
                'abierto' => 'bg-green-100 text-green-800 ring-1 ring-green-400',
                'cerrado' => 'bg-red-100 text-red-800 ring-1 ring-red-400',
                'inactivo' => 'bg-stone-200 text-stone-700 ring-1 ring-stone-400',
                default => 'bg-amber-100 text-amber-800 ring-1 ring-amber-400',
            };
        @endphp

        <article class="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-stone-100 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold text-stone-900">{{ $sitio->nombre }}</h1>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-stone-500">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                            </svg>
                            {{ $sitio->direccion }}
                        </p>
                    </div>

                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $clasesInsignia }}">
                        <span class="h-2 w-2 rounded-full {{ $estado?->estado === 'abierto' ? 'bg-green-500' : ($estado?->estado === 'cerrado' ? 'bg-red-500' : 'bg-stone-500') }}"></span>
                        {{ $estado?->estado ?? 'indeterminado' }}
                    </span>
                </div>

                @if ($sitio->descripcion)
                    <p class="mt-4 text-sm leading-relaxed text-stone-600">{{ $sitio->descripcion }}</p>
                @endif

                <dl class="mt-5 grid grid-cols-2 gap-4 rounded-lg bg-stone-50 p-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Horario de atención</dt>
                        <dd class="mt-1 font-semibold text-stone-800">{{ $sitio->horarioAtencion() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Estado administrativo</dt>
                        <dd class="mt-1 font-semibold {{ $sitio->activo ? 'text-green-700' : 'text-stone-600' }}">
                            {{ $sitio->activo ? 'Activo' : 'Temporalmente inactivo' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Registrado</dt>
                        <dd class="mt-1 font-semibold text-stone-800">{{ $sitio->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Última actualización</dt>
                        <dd class="mt-1 font-semibold text-stone-800">{{ $sitio->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="p-6">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Evaluación de estado de operatividad</h2>

                @if ($estado === null)
                    <p class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        El horario de atención registrado no es coherente, por lo que no fue posible evaluar el estado del sitio.
                    </p>
                @endif

                @include('sitios.partials.verificar', ['sitio' => $sitio, 'estadoInicial' => $estado])
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 bg-stone-50 px-6 py-4">
                <a href="{{ route('sitios.index') }}" class="text-sm font-medium text-stone-600 hover:text-stone-900 hover:underline">&larr; Volver al listado</a>
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('sitios.edit', $sitio) }}" class="font-medium text-amber-800 hover:text-amber-900 hover:underline">Editar</a>
                    <form method="POST" action="{{ route('sitios.destroy', $sitio) }}" onsubmit="return confirm('¿Eliminar el sitio patrimonial &quot;{{ addslashes($sitio->nombre) }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-medium text-red-700 hover:text-red-800 hover:underline">Eliminar</button>
                    </form>
                </div>
            </div>
        </article>
    </div>
@endsection
