<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CuscoGO')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand" aria-label="CuscoGO inicio">
                <span class="brand-mark" aria-hidden="true"></span>
                <span class="brand-text">CuscoGO</span>
            </a>
            <nav class="main-nav" aria-label="Principal">
                <a href="{{ route('aforo.index') }}" data-cy="nav-aforo">Aforo</a>
                <a href="{{ url('/resenas') }}" data-cy="nav-resenas">Reseñas</a>
                <a href="{{ url('/operatividad') }}" data-cy="nav-operatividad">Operatividad</a>
                <a href="{{ url('/transporte') }}" data-cy="nav-transporte">Transporte</a>
            </nav>
        </div>
    </header>

    <main id="contenido" class="container main-content">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>Guía turística de Cusco · CuscoGO</p>
        </div>
    </footer>

    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>