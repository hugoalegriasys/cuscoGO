@extends('layouts.app')

@section('title', 'Reseñas - CuscoGO')

@section('content')
<section class="page-header">
    <p class="eyebrow">HU-02</p>
    <h1>Registrar y consultar reseñas</h1>
    <p>Elige un sitio turístico para ver su promedio y reseñas, y comparte tu experiencia.</p>
</section>

<section class="card form-card" aria-labelledby="titulo-resenas">
    <h2 id="titulo-resenas">Sitio turístico</h2>
    <div class="field">
        <label for="sitio-resena">Selecciona un sitio</label>
        <select id="sitio-resena" name="sitio" data-cy="resena-sitio-select">
            <option value="">Selecciona un sitio</option>
            @foreach ($sitios as $sitio)
                <option value="{{ $sitio->id }}">{{ $sitio->nombre }}</option>
            @endforeach
        </select>
    </div>
</section>

<section class="card" aria-labelledby="titulo-resumen-resenas" data-cy="resena-resumen">
    <h2 id="titulo-resumen-resenas">Promedio y reseñas</h2>
    <p class="result-label">Promedio de calificaciones</p>
    <p class="promedio-valor" data-cy="resena-promedio">0</p>
    <div id="resena-lista-estado" class="status-area" aria-live="polite" data-cy="resena-lista-estado"></div>
    <ul id="resena-lista" class="resena-lista" data-cy="resena-lista" aria-label="Lista de reseñas"></ul>
</section>

<section class="card form-card" aria-labelledby="titulo-nueva-resena">
    <h2 id="titulo-nueva-resena">Registrar nueva reseña</h2>
    <form id="form-resena" data-cy="resena-form" novalidate>
        <fieldset class="field" data-cy="resena-calificacion-field">
            <legend>Calificación</legend>
            <p class="field-help">Selecciona de 1 a 5 estrellas (usa teclado con flechas o tabulación y Enter/Espacio).</p>
            <div class="rating" role="radiogroup" aria-label="Calificación de 1 a 5 estrellas" data-cy="resena-estrellas">
                @for ($i = 1; $i <= 5; $i++)
                    <input type="radio" id="estrella-{{ $i }}" name="calificacion" value="{{ $i }}" class="rating-input" data-cy="resena-estrella-{{ $i }}">
                    <label for="estrella-{{ $i }}" class="rating-label" aria-label="{{ $i }} {{ $i === 1 ? 'estrella' : 'estrellas' }}">★</label>
                @endfor
            </div>
        </fieldset>

        <div class="field">
            <label for="comentario-resena">Comentario</label>
            <textarea id="comentario-resena" name="comentario" rows="4" maxlength="500" data-cy="resena-comentario" aria-describedby="comentario-ayuda comentario-contador"></textarea>
            <p id="comentario-ayuda" class="field-help">Máximo 500 caracteres.</p>
            <p id="comentario-contador" class="char-counter" data-cy="resena-contador">0/500</p>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary" data-cy="resena-enviar-btn">Registrar reseña</button>
        </div>
    </form>
    <div id="resena-form-estado" class="status-area" aria-live="polite" data-cy="resena-form-estado"></div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/resenas.js') }}" defer></script>
@endpush