@extends('layouts.app')

@section('title', 'CuscoGO - Guía turística de Cusco')

@section('content')
<section class="hero" data-cy="home-hero">
    <p class="eyebrow">Bienvenido a Cusco</p>
    <h1>Descubre la ciudad imperial con información clara y útil</h1>
    <p>Consulta aforo, reseñas, operatividad y transporte turístico desde un solo lugar.</p>
</section>

<section class="grid-cards" aria-label="Secciones principales">
    <a class="card-link" href="{{ route('aforo.index') }}" data-cy="card-aforo">
        <article class="card">
            <h2>Aforo en tiempo real</h2>
            <p>Revisa si un sitio está en nivel Bajo, Moderado o Alto antes de visitarlo.</p>
            <span class="card-cta">Consultar aforo</span>
        </article>
    </a>
    <a class="card-link" href="{{ route('resenas.index') }}" data-cy="card-resenas">
        <article class="card">
            <h2>Reseñas</h2>
            <p>Registra tu experiencia y conoce el promedio de calificaciones.</p>
            <span class="card-cta">Ver reseñas</span>
        </article>
    </a>
    <a class="card-link" href="{{ url('/operatividad') }}" data-cy="card-operatividad">
        <article class="card">
            <h2>Operatividad</h2>
            <p>Verifica si un sitio patrimonial está abierto o cerrado ahora.</p>
            <span class="card-cta">Consultar operatividad</span>
        </article>
    </a>
    <a class="card-link" href="{{ url('/transporte') }}" data-cy="card-transporte">
        <article class="card">
            <h2>Transporte turístico</h2>
            <p>Consulta servicios disponibles y calcula el costo de tu traslado.</p>
            <span class="card-cta">Calcular transporte</span>
        </article>
    </a>
</section>
@endsection