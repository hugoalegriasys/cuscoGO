@extends('layouts.app')

@section('title', 'CuscoGO - Atractivos Turísticos y Reseñas')

@section('content')
<div class="space-y-8">
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-[#1b1b18] to-[#2d2c27] text-white rounded-2xl p-8 sm:p-10 shadow-lg relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-block px-3 py-1 bg-amber-500/20 text-amber-400 text-xs font-semibold rounded-full border border-amber-500/30 mb-3">
                CuscoGO • Plataforma Turística
            </span>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight mb-3">
                Sitios Turísticos de Cusco
            </h1>
            <p class="text-gray-300 text-sm sm:text-base leading-relaxed">
                Explora los principales atractivos de la capital histórica del Perú, consulta las calificaciones y opiniones de otros visitantes en tiempo real, y comparte tu propia experiencia.
            </p>
        </div>
        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Tourist Sites Grid -->
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Atractivos Disponibles</h2>
                <p class="text-sm text-gray-500">Selecciona un sitio turístico para acceder a su sección de reseñas y calificarlo.</p>
            </div>
            <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg font-medium">
                {{ count($sitios) }} Sitios Registrados
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($sitios as $id => $sitio)
                <div data-cy="site-card" data-cy-site="{{ $id }}" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div>
                                <h3 data-cy="site-title" class="text-lg font-bold text-gray-900 hover:text-amber-600 transition-colors">
                                    {{ $sitio['nombre'] }}
                                </h3>
                                <p class="text-xs text-amber-700 font-medium">{{ $sitio['subtitulo'] }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="inline-flex items-center gap-1 bg-amber-50 border border-amber-200 text-amber-900 px-2.5 py-1 rounded-lg text-sm font-bold">
                                    <span>★</span>
                                    <span data-cy="average-rating">{{ number_format($sitio['promedio'], 1) }}</span>
                                </div>
                                <span class="block text-[11px] text-gray-400 mt-0.5" data-cy="total-reviews">
                                    {{ $sitio['total_resenas'] }} {{ $sitio['total_resenas'] == 1 ? 'reseña' : 'reseñas' }}
                                </span>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 line-clamp-2 mb-4 leading-relaxed">
                            {{ $sitio['descripcion'] }}
                        </p>

                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 bg-gray-50 p-3 rounded-lg mb-4">
                            <div>
                                <span class="font-medium text-gray-700">Ubicación:</span>
                                <span class="block truncate">{{ $sitio['ubicacion'] }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Horario:</span>
                                <span class="block">{{ $sitio['horario'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500">
                            {{ $sitio['altitud'] }}
                        </span>
                        <a href="{{ route('sitios.show', $id) }}"
                           data-cy="enter-site"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-[#1b1b18] hover:bg-black text-white text-xs font-semibold rounded-lg transition-colors shadow-xs">
                            <span>Ingresar al Sitio / Acceder a reseñas</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
