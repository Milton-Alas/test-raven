<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Candidato</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-2xl p-8 my-8 space-y-6 bg-white rounded-lg shadow-md">

        <h1 class="text-2xl font-bold text-center text-gray-900">Registro de Nuevo Candidato</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="text-sm font-medium text-gray-700">Nombre Completo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="mt-4">
                <label for="email" class="text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- DUI/NIT -->
            <div class="mt-4">
                <label for="dui_nit" class="text-sm font-medium text-gray-700">DUI o NIT</label>
                <input id="dui_nit" type="text" name="dui_nit" value="{{ old('dui_nit') }}" required
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('dui_nit')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Age -->
            <div class="mt-4">
                <label for="age" class="text-sm font-medium text-gray-700">Edad</label>
                <input id="age" type="number" name="age" value="{{ old('age') }}" required
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('age')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Occupation -->
            <div class="mt-4">
                <label for="occupation" class="text-sm font-medium text-gray-700">Ocupación</label>
                <input id="occupation" type="text" name="occupation" value="{{ old('occupation') }}" required
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('occupation')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Education Level -->
            <div class="mt-4">
                <label for="education_level" class="text-sm font-medium text-gray-700">Nivel de Estudios</label>
                <input id="education_level" type="text" name="education_level" value="{{ old('education_level') }}" required
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('education_level')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mt-4">
                <label for="password" class="text-sm font-medium text-gray-700">Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <label for="password_confirmation" class="text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="block w-full px-3 py-2 mt-1 text-gray-900 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div class="flex flex-col items-center justify-end mt-6">
                <button type="submit" class="w-full px-4 py-2 font-bold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Registrarme
                </button>

                <a href="{{ route('login') }}" class="mt-4 text-sm text-indigo-600 hover:text-indigo-500">
                    ¿Ya tienes una cuenta? Inicia Sesión
                </a>
            </div>
        </form>
    </div>
</body>
</html>
