@extends('layouts.app')

@section('titulo', 'Registrar Sitio Patrimonial · cuscoGO')

@section('contenido')
    <div class="max-w-2xl mx-auto">
        <nav class="mb-4 text-sm text-stone-500" aria-label="Ruta de navegación">
            <a href="{{ route('sitios.index') }}" class="hover:text-amber-700 font-medium">Sitios Patrimoniales</a>
            <span class="mx-2" aria-hidden="true">/</span>
            <span class="text-stone-700" aria-current="page">Registrar sitio</span>
        </nav>

        <header class="mb-6">
            <h1 class="text-2xl font-bold text-stone-900">Registrar Sitio Patrimonial</h1>
            <p class="mt-1 text-sm text-stone-500">
                Registra el horario de atención para que los turistas puedan consultar su estado de operatividad.
            </p>
        </header>

        <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm">
            @include('sitios.partials.form', [
                'accion' => route('sitios.store'),
                'metodo' => 'POST',
                'sitio' => null,
                'etiquetaBoton' => 'Registrar sitio',
            ])
        </div>
    </div>
@endsection
