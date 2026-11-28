@extends('layouts.app')

@section('title', $sitio['nombre'] . ' - Reseñas | CuscoGO')

@section('content')
<div class="space-y-8">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-amber-600 font-medium transition-colors">
            <span>←</span>
            <span>Volver a la lista de atractivos</span>
        </a>
        <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
            Sitio: {{ $sitio['nombre'] }}
        </span>
    </div>

    <!-- Site Header Card -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-gray-100">
            <div class="space-y-2">
                <span class="text-xs uppercase tracking-wider font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-md">
                    {{ $sitio['subtitulo'] }}
                </span>
                <h1 data-cy="site-title" class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                    {{ $sitio['nombre'] }}
                </h1>
                <p class="text-sm text-gray-600 max-w-2xl leading-relaxed">
                    {{ $sitio['descripcion'] }}
                </p>
                <div class="flex flex-wrap gap-4 text-xs text-gray-500 pt-2">
                    <span class="flex items-center gap-1">
                        <strong>📍 Ubicación:</strong> {{ $sitio['ubicacion'] }}
                    </span>
                    <span class="flex items-center gap-1">
                        <strong>⛰️ Altitud:</strong> {{ $sitio['altitud'] }}
                    </span>
                    <span class="flex items-center gap-1">
                        <strong>🕒 Horario:</strong> {{ $sitio['horario'] }}
                    </span>
                </div>
            </div>

            <!-- Average Rating Badge Card -->
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/70 rounded-xl p-5 text-center shrink-0 min-w-[200px]">
                <span class="text-xs uppercase font-bold text-amber-800 tracking-wider">Calificación Promedio</span>
                <div class="my-2 flex items-center justify-center gap-1">
                    <span class="text-4xl font-extrabold text-amber-700" data-cy="average-rating">
                        {{ number_format($promedio, 1) }}
                    </span>
                    <span class="text-amber-500 text-2xl">★</span>
                </div>
                <div class="text-amber-500 text-sm tracking-widest mb-1">
                    @php
                        $roundedRating = (int) round($promedio);
                    @endphp
                    @for ($i = 1; $i <= 5; $i++)
                        <span>{{ $i <= $roundedRating ? '★' : '☆' }}</span>
                    @endfor
                </div>
                <span data-cy="total-reviews" class="text-xs text-amber-900/80 font-medium">
                    {{ $totalResenas }} {{ $totalResenas == 1 ? 'reseña registrada' : 'reseñas registradas' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Notification Messages -->
    @if (session('success'))
        <div data-cy="success-message" class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl shadow-xs flex items-center gap-3">
            <span class="text-emerald-600 text-xl font-bold">✓</span>
            <div>
                <h4 class="text-sm font-bold text-emerald-900">¡Operación exitosa!</h4>
                <p class="text-sm text-emerald-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div data-cy="error-message" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-xs">
            <div class="flex items-start gap-3">
                <span class="text-red-600 text-xl font-bold">✕</span>
                <div>
                    <h4 class="text-sm font-bold text-red-900 mb-1">Por favor corrige los siguientes errores:</h4>
                    <ul class="text-sm text-red-800 space-y-1 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Reviews Section Container -->
    <div data-cy="reviews-section" id="seccion-resenas" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Form Column (5 cols) -->
        <div class="lg:col-span-5 bg-white border border-gray-200 rounded-2xl p-6 sm:p-7 shadow-xs">
            <div class="mb-5">
                <h2 class="text-lg font-bold text-gray-900">Publicar una Reseña</h2>
                <p class="text-xs text-gray-500">Completa el formulario para registrar una reseña de este sitio turístico.</p>
            </div>

            <form action="{{ route('resenas.store') }}" method="POST" data-cy="review-form" class="space-y-5">
                @csrf
                <input type="hidden" name="sitio_id" value="{{ $sitio['id'] }}">

                <!-- 3. Seleccionar una calificación -->
                <div>
                    <label for="calificacion" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                        Calificación (1 a 5 estrellas) <span class="text-red-500">*</span>
                    </label>
                    <select name="calificacion"
                            id="calificacion"
                            data-cy="rating-select"
                            class="w-full bg-gray-50 border @error('calificacion') border-red-400 bg-red-50/30 @else border-gray-300 @enderror rounded-lg px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        <option value="">-- Selecciona una calificación --</option>
                        <option value="5" {{ old('calificacion') == '5' ? 'selected' : '' }}>⭐⭐⭐⭐⭐ 5 estrellas (Excelente)</option>
                        <option value="4" {{ old('calificacion') == '4' ? 'selected' : '' }}>⭐⭐⭐⭐ 4 estrellas (Muy bueno)</option>
                        <option value="3" {{ old('calificacion') == '3' ? 'selected' : '' }}>⭐⭐⭐ 3 estrellas (Bueno)</option>
                        <option value="2" {{ old('calificacion') == '2' ? 'selected' : '' }}>⭐⭐ 2 estrellas (Regular)</option>
                        <option value="1" {{ old('calificacion') == '1' ? 'selected' : '' }}>⭐ 1 estrella (Deficiente)</option>
                    </select>
                    @error('calificacion')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 4. Escribir un comentario -->
                <div>
                    <label for="comentario" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                        Comentario <span class="text-red-500">*</span>
                    </label>
                    <textarea name="comentario"
                              id="comentario"
                              data-cy="comment-input"
                              rows="4"
                              placeholder="Escribe tu comentario sobre tu visita a este atractivo turístico..."
                              class="w-full bg-gray-50 border @error('comentario') border-red-400 bg-red-50/30 @else border-gray-300 @enderror rounded-lg p-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">{{ old('comentario') }}</textarea>
                    @error('comentario')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-gray-400 mt-1">Los comentarios vacíos o con solo espacios serán rechazados.</p>
                </div>

                <!-- 5. Presionar «Publicar reseña» -->
                <div>
                    <button type="submit"
                            data-cy="submit-button"
                            class="w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-semibold rounded-lg text-sm transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                        <span>Publicar reseña</span>
                        <span>✍️</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Reviews List Column (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Reseñas Registradas</h2>
                <span class="text-xs font-medium text-gray-500">
                    Mostrando {{ $totalResenas }} {{ $totalResenas == 1 ? 'opinión' : 'opiniones' }}
                </span>
            </div>

            <div data-cy="reviews-list" class="space-y-4">
                @forelse ($resenas as $r)
                    <div data-cy="review-item" class="bg-white border border-gray-200 rounded-xl p-5 shadow-xs hover:border-amber-300 transition-colors">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <span data-cy="review-rating" class="font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded text-xs flex items-center gap-1">
                                    <span>{{ $r['calificacion'] }}</span>
                                    <span>★</span>
                                </span>
                                <div class="text-amber-400 text-xs tracking-wider">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <span>{{ $s <= $r['calificacion'] ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                            </div>
                            <span data-cy="review-date" class="text-[11px] text-gray-400">
                                {{ isset($r['created_at']) ? \Carbon\Carbon::parse($r['created_at'])->format('d/m/Y H:i') : 'Reciente' }}
                            </span>
                        </div>
                        <p data-cy="review-comment" class="text-sm text-gray-800 leading-relaxed break-words whitespace-pre-line">
                            {{ $r['comentario'] }}
                        </p>
                    </div>
                @empty
                    <div data-cy="no-reviews" class="bg-white border border-dashed border-gray-300 rounded-xl p-10 text-center text-gray-500">
                        <div class="text-3xl mb-2">🏔️</div>
                        <p class="font-medium text-gray-700">Aún no hay reseñas registradas para este sitio turístico.</p>
                        <p class="text-xs text-gray-400 mt-1">¡Sé la primera persona en calificar y dejar tu comentario!</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
