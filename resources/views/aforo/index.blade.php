@extends('layouts.app')

@section('title', 'Aforo en tiempo real - CuscoGO')

@section('content')
<section class="page-header">
    <p class="eyebrow">HU-01</p>
    <h1>Consulta de aforo en tiempo real</h1>
    <p>Elige un sitio turístico para conocer su nivel de aforo actual. Datos del sensor de demostración en entorno local.</p>
</section>

<section class="card form-card" aria-labelledby="titulo-aforo">
    <h2 id="titulo-aforo">Consultar aforo</h2>
    <form id="form-aforo" data-cy="aforo-form" novalidate>
        <div class="field">
            <label for="sitio-aforo">Sitio turístico</label>
            <select id="sitio-aforo" name="sitio" required data-cy="aforo-sitio-select">
                <option value="">Selecciona un sitio</option>
                @foreach ($sitios as $sitio)
                    <option value="{{ $sitio->slug }}">{{ $sitio->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="btn btn-primary" data-cy="aforo-consultar-btn">Consultar aforo</button>
        </div>
    </form>

    <div id="aforo-estado" class="status-area" aria-live="polite" data-cy="aforo-estado"></div>
    <div id="aforo-resultado" class="result-area" data-cy="aforo-resultado" hidden>
        <p class="result-label">Nivel de aforo</p>
        <p id="aforo-nivel" class="badge" data-cy="aforo-nivel"></p>
        <p id="aforo-detalle" class="result-detail" data-cy="aforo-detalle"></p>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/aforo.js') }}" defer></script>
@endpush