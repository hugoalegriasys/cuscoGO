@extends('layouts.app')

@section('titulo', "Editar {$sitio->nombre} · cuscoGO")

@section('contenido')
    <div class="max-w-2xl mx-auto">
        <nav class="mb-4 text-sm text-stone-500" aria-label="Ruta de navegación">
            <a href="{{ route('sitios.index') }}" class="hover:text-amber-700 font-medium">Sitios Patrimoniales</a>
            <span class="mx-2" aria-hidden="true">/</span>
            <a href="{{ route('sitios.show', $sitio) }}" class="hover:text-amber-700 font-medium">{{ $sitio->nombre }}</a>
            <span class="mx-2" aria-hidden="true">/</span>
            <span class="text-stone-700" aria-current="page">Editar</span>
        </nav>

        <header class="mb-6">
            <h1 class="text-2xl font-bold text-stone-900">Editar {{ $sitio->nombre }}</h1>
            <p class="mt-1 text-sm text-stone-500">Actualiza los datos y el horario de atención del sitio patrimonial.</p>
        </header>

        <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm">
            @include('sitios.partials.form', [
                'accion' => route('sitios.update', $sitio),
                'metodo' => 'PUT',
                'sitio' => $sitio,
                'etiquetaBoton' => 'Guardar cambios',
            ])
        </div>
    </div>
@endsection
