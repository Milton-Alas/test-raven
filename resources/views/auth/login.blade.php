<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login de Candidato</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ues-blue': '#0047AB',
                        'ues-red': '#E60000',
                        'ues-gold': '#FFCC00',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-8">

    <div class="w-full max-w-md px-8 py-10 bg-white rounded-xl shadow-lg">
        
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-ues-blue rounded-full mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-ues-blue">Acceso de Candidatos</h1>
            <p class="text-gray-600 text-sm mt-2">Ingresa tus credenciales para continuar</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-6 p-3 bg-green-50 border-l-4 border-green-500 rounded">
                <p class="text-sm text-green-700">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Login (Email o DUI/NIT) -->
            <div>
                <label for="login" class="block text-sm font-semibold text-gray-700 mb-2">
                    Email o DUI/NIT
                </label>
                <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                       class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                @error('login')
                    <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                    Contraseña
                </label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                 @error('password')
                    <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input id="remember_me" type="checkbox" 
                       class="w-4 h-4 rounded border-gray-300 text-ues-blue focus:ring-2 focus:ring-ues-blue" 
                       name="remember">
                <label for="remember_me" class="ml-2 text-sm text-gray-700">
                    Recordar sesión
                </label>
            </div>

            <!-- Buttons -->
            <div class="space-y-4 pt-2">
                <button type="submit" 
                        class="w-full px-4 py-3 font-semibold text-white bg-ues-red rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ues-red transition shadow-md">
                    Iniciar Sesión
                </button>

                <div class="text-center">
                    <a href="{{ route('register') }}" 
                       class="text-sm text-ues-blue hover:text-blue-800 font-medium transition">
                        ¿No tienes una cuenta? <span class="underline">Regístrate</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500">Sistema de Gestión de Candidatos</p>
        </div>
    </div>

</body>
</html>