

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ExpedientesPro') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=Nunito:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <!-- TomSelect CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <!-- TomSelect JS (lo ponemos 'defer' para que no bloquee la carga) -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js" defer></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Scripts originales de Laravel (comentados para evitar conflictos) -->
    {{-- @vite(['resources/sass/app.scss', 'resources/js/app.js']) --}}
    
    <style>
        /* Asegurar que Tailwind tenga prioridad */
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #f8f9fa;
            --accent-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #343a40;
            --light-color: #ffffff;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), #4a90e2);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(44, 90, 160, 0.3);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 1rem 1.5rem;
        }
        
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-container img {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 200px;
            height: auto;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .stats-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .icon-primary { background: rgba(44, 90, 160, 0.2); color: var(--primary-color); }
        .icon-success { background: rgba(40, 167, 69, 0.2); color: var(--accent-color); }
        .icon-warning { background: rgba(255, 193, 7, 0.2); color: var(--warning-color); }
        .icon-info { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }
        * {
            box-sizing: border-box;
        }
    </style>
    @stack('head')
</head>
<body class="bg-gray-100 font-sans antialiased"> {{-- Un fondo neutro para el body --}}
    <div id="app">
        
        {{-- Aquí va tu NAV completo --}}
        <nav class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-50">
            <div class=" mx-auto px-3">
                <div class="flex justify-between items-center py-4">
                    <!-- Brand/Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <a href="{{ url('/') }}" class="text-xl font-bold text-gray-800 hover:text-blue-600 transition-colors duration-200">
                            {{ config('ExpedientesPRO', 'ExpedientesPRO') }}
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        @role('admin')
                            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Gestion de Usuarios
                            </a>
                            <a href="{{ route('admin.reportes.metas.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Reporte Metas
                            </a>
                            <a href="{{ route('admin.metas.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Asignar Metas
                            </a>
                            <a href="{{ route('admin.columnas-maestras.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Gestión de Columnas Maestras
                            </a>
                            <a href="{{ url('/admin/gestion-datos') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                </svg>
                                Gestión de Datos
                            </a>
                        @endrole
                        @role('admin|encargado')
                            <a href="{{ route('ingesta.create') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Subir Excel
                            </a>
                            <a href="{{ route('generador.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Generar Expediente
                            </a>
                        @endrole
                        @role('consultor|admin|encargado')
                            <a href="{{route('admin.tipos-documento.index')}}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Tipos de Documento
                            </a>
                            
                            <a href="{{ route('expedientes.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                                Expedientes
                            </a>
                        @endrole
                    </div>

                    <!-- Right Side Navigation -->
                    <div class="flex items-center space-x-4">
                        @guest
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="text-gray-600 hover:text-blue-600 transition-colors duration-200 font-medium">
                                    {{ __('Login') }}
                                </a>
                            @endif
                           
                        @else
                            <!-- User Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown()" class="flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors duration-200 focus:outline-none">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-medium">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                    </div>
                                    <span class="hidden md:block font-medium">{{ Auth::user()->name }}</span>
                                    <svg class="w-4 h-4 transition-transform duration-200" id="dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50">
                                    <div class="px-4 py-2 border-b border-gray-100">
                                        <p class="text-sm font-medium text-gray-800">{{ Auth::user()->name }}</p>
                                        <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                    </div>
                                    <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors duration-200 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Mi Perfil
                                    </a>
                                    
                                    <hr class="my-2">
                                    <a href="{{ route('logout') }}" 
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                       class="block px-4 py-2 text-red-600 hover:bg-red-50 transition-colors duration-200 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        {{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                        @csrf
                                    </form>
                                </div>
                            </div>
                        @endguest

                        <!-- Mobile menu button -->
                        <button class="md:hidden p-2 text-gray-600 hover:text-blue-600 transition-colors duration-200" onclick="toggleMobileMenu()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile Navigation -->
                <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 py-4 space-y-2">
                @role('admin')
                    <a href="{{ route('admin.users.index') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Gestion de Usuarios
                    </a>
                    <a href="{{ route('admin.reportes.metas.index') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h3l2-2h4l2 2h3a2 2 0 012 2v12a2 2 0 01-2 2z"></path>
                        </svg>
                        Reporte Metas
                    </a>
                    <a href="{{ route('admin.metas.index') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.879 9.168-5.168"/>
                        </svg>
                        Asignar Metas
                    </a>
                    <a href="{{ route('admin.columnas-maestras.index') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10h10M7 14h10"></path>
                        </svg>
                        Gestión de Columnas Maestras
                    </a>
                    <a href="{{ url('/admin/gestion-datos') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10h10M7 14h10"></path>
                        </svg>
                        Gestión de Datos
                    </a>
                @endrole

                @role('admin|encargado')
                    <a href="{{ route('ingesta.create') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Subir Excel
                    </a>
                    <a href="{{ route('generador.index') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Generar Expediente
                    </a>
                @endrole

                @role('consultor|admin|encargado')
                    <a href="{{ route('admin.tipos-documento.index') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h3l2-2h4l2 2h3a2 2 0 012 2v12a2 2 0 01-2 2z"/>
                        </svg>
                        Tipos de Documento
                    </a>
                    <a href="{{ route('expedientes.index') }}" class="block py-2 px-4 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10h10M7 14h10"></path>
                        </svg>
                        Expedientes
                    </a>
                @endrole
                

                </div>

            </div>
        </nav>


        <!-- Main Content -->
        {{-- Este main ahora tendrá el fondo y contendrá todo lo demás --}}
        <main class="w-full">
            @yield('content')
        </main>

    </div>

    
    <!-- JavaScript para funcionalidad del dropdown y menú móvil -->
    <script>
        function toggleDropdown() {
            const menu = document.getElementById('dropdown-menu');
            const arrow = document.getElementById('dropdown-arrow');
            
            menu.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }

        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdown-menu');
            const button = event.target.closest('button');
            
            if (!button || button.getAttribute('onclick') !== 'toggleDropdown()') {
                dropdown.classList.add('hidden');
                document.getElementById('dropdown-arrow').classList.remove('rotate-180');
            }
        });

        // Cerrar menú móvil al cambiar tamaño de pantalla
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                document.getElementById('mobile-menu').classList.add('hidden');
            }
        });
    </script>
    @stack('scripts') {{-- Para los scripts específicos de cada página --}}
</body>
</html>