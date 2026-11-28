<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CuscoGO - Plataforma de Gestión Turística')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Tailwind CSS CDN for instant rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            gold: '#F8B803',
                            red: '#F53003',
                            dark: '#1b1b18',
                        }
                    },
                    fontFamily: {
                        sans: ['Instrument Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen flex flex-col font-sans antialiased">
    <!-- Navbar -->
    <header class="border-b border-gray-200 bg-white sticky top-0 z-50 shadow-xs">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center space-x-3">
                <span class="w-9 h-9 rounded-lg bg-gradient-to-tr from-amber-500 to-red-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                    C
                </span>
                <div>
                    <span class="font-bold text-xl tracking-tight text-gray-900">Cusco<span class="text-amber-500">GO</span></span>
                    <span class="hidden sm:inline-block text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full ml-2">v1.0</span>
                </div>
            </a>
            <nav class="flex items-center space-x-6 text-sm font-medium">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-amber-600 transition-colors">Sitios Turísticos</a>
                <a href="{{ route('sitios.show', 'machu-picchu') }}" class="text-gray-700 hover:text-amber-600 transition-colors">Reseñas</a>
                <span class="text-xs bg-amber-50 border border-amber-200 text-amber-800 px-2.5 py-1 rounded-full font-semibold">
                    CUS-04 Reseñas
                </span>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 bg-white mt-12 py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-sm text-gray-500 gap-4">
            <p><strong>CuscoGO</strong> — Plataforma Integral de Gestión Turística en Tiempo Real.</p>
            <p class="text-xs text-gray-400">Módulos: CUS-01 Aforo | CUS-02 Transporte | CUS-03 Horarios | CUS-04 Reseñas</p>
        </div>
    </footer>
</body>
</html>
