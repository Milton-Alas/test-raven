<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Candidato</title>
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
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-12">

    <div class="w-full max-w-2xl px-8 py-10 my-8 bg-white rounded-xl shadow-lg">

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-ues-blue rounded-full mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-ues-blue">Registro de Nuevo Candidato</h1>
            <p class="text-gray-600 text-sm mt-2">Completa el formulario para crear tu cuenta</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <!-- Sección: Información Personal -->
            <div class="border-l-4 border-ues-gold pl-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Información Personal</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Name -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nombre Completo</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                        @error('name')
                            <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                        @error('email')
                            <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- DUI/NIT -->
                    <div>
                        <label for="dui_nit" class="block text-sm font-semibold text-gray-700 mb-2">DUI o NIT</label>
                        <input id="dui_nit" type="text" name="dui_nit" value="{{ old('dui_nit') }}" required
                               class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                        @error('dui_nit')
                            <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Age -->
                    <div>
                        <label for="age" class="block text-sm font-semibold text-gray-700 mb-2">Edad</label>
                        <input id="age" type="number" name="age" value="{{ old('age') }}" required
                               class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                        @error('age')
                            <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Occupation -->
                    <div>
                        <label for="occupation" class="block text-sm font-semibold text-gray-700 mb-2">Ocupación</label>
                        <input id="occupation" type="text" name="occupation" value="{{ old('occupation') }}" required
                               class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                        @error('occupation')
                            <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Sección: Información Académica -->
            <div class="border-l-4 border-ues-gold pl-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Información Académica</h2>
                
                <!-- Education Level -->
                <div>
                    <label for="education_level" class="block text-sm font-semibold text-gray-700 mb-2">Nivel de Estudios</label>
                    <input id="education_level" type="text" name="education_level" value="{{ old('education_level') }}" required
                           class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                    @error('education_level')
                        <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Sección: Seguridad -->
            <div class="border-l-4 border-ues-gold pl-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Seguridad</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Contraseña</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                               class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                        @error('password')
                            <p class="mt-2 text-sm text-ues-red">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="block w-full px-4 py-3 text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ues-blue focus:border-transparent transition">
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="space-y-4 pt-4">
                <button type="submit" 
                        class="w-full px-4 py-3 font-semibold text-white bg-ues-red rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ues-red transition shadow-md">
                    Registrarme
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" 
                       class="text-sm text-ues-blue hover:text-blue-800 font-medium transition">
                        ¿Ya tienes una cuenta? <span class="underline">Inicia Sesión</span>
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