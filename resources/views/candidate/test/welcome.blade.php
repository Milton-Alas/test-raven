@extends('candidate.layouts.app')

@section('title', 'Bienvenida al Test')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-6 py-4">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-6 py-4">
                {{ session('success') }}
            </div>
        @endif
        <!-- Header -->
        <div class="bg-gradient-to-r from-ues-blue to-blue-700 px-8 py-6">
            <h1 class="text-3xl font-bold text-white text-center">
                Test de Matrices Progresivas de Raven
            </h1>
            <p class="text-blue-100 text-center mt-2">
                Escala General para Adultos
            </p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Información del Candidato -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6 border-l-4 border-ues-gold">
                <h3 class="font-semibold text-ues-blue mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Información del Candidato
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Nombre:</span>
                        <span class="font-medium text-slate-900 ml-2">{{ $candidate->name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Edad:</span>
                        <span class="font-medium text-slate-900 ml-2">{{ $candidate->age }} años</span>
                    </div>
                    @if($candidate->occupation)
                    <div>
                        <span class="text-gray-600">Ocupación:</span>
                        <span class="font-medium text-slate-900 ml-2">{{ $candidate->occupation }}</span>
                    </div>
                    @endif
                    @if($candidate->education_level)
                    <div>
                        <span class="text-gray-600">Educación:</span>
                        <span class="font-medium text-slate-900 ml-2">{{ ucfirst($candidate->education_level) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Instrucciones -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-ues-blue mb-4">Instrucciones</h3>

                <div class="space-y-4 text-slate-700">
                    <p>
                        El Test de Matrices Progresivas de Raven es una prueba de inteligencia no verbal que mide
                        la capacidad de razonamiento abstracto y la resolución de problemas.
                    </p>

                    <!-- ¿Cómo funciona? -->
                    <div class="bg-blue-50 border-l-4 border-ues-blue p-4 rounded-lg">
                        <h4 class="font-semibold text-ues-blue mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            ¿Cómo funciona?
                        </h4>
                        <ul class="list-disc list-inside space-y-2 text-slate-700 ml-1">
                            <li>Verás una serie de figuras con una parte faltante</li>
                            <li>Debes elegir cuál de las 6 opciones completa correctamente el patrón</li>
                            <li>El test consta de <strong class="text-slate-900">60 preguntas</strong> divididas en 5 series (A, B, C, D, E)</li>
                            <li>Las preguntas se vuelven progresivamente más difíciles</li>
                        </ul>
                    </div>

                    <!-- Importante -->
                    <div class="bg-yellow-50 border-l-4 border-ues-gold p-4 rounded-lg">
                        <h4 class="font-semibold text-slate-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-ues-gold" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Importante:
                        </h4>
                        <ul class="list-disc list-inside space-y-2 text-slate-700 ml-1">
                            <li>Tienes <strong class="text-ues-red">45 minutos</strong> para completar el test</li>
                            <li>Una vez que selecciones una respuesta, <strong class="text-slate-900">no podrás regresar</strong></li>
                            <li>Tu progreso se guardará automáticamente</li>
                            <li><strong class="text-ues-red">Solo puedes realizar el test UNA vez</strong></li>
                            <li>No cierres el navegador ni actualices la página durante el test</li>
                        </ul>
                    </div>

                    <!-- Recomendaciones -->
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                        <h4 class="font-semibold text-green-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Recomendaciones:
                        </h4>
                        <ul class="list-disc list-inside space-y-2 text-slate-700 ml-1">
                            <li>Trabaja en un lugar tranquilo sin distracciones</li>
                            <li>Lee cuidadosamente cada pregunta</li>
                            <li>No te apresures, pero tampoco pases demasiado tiempo en una sola pregunta</li>
                            <li>Confía en tu primera impresión</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botón Iniciar -->
            <form method="POST" action="{{ route('candidate.test.start') }}">
                @csrf
                <div class="flex flex-col items-center">
                    <button
                        type="submit"
                        class="bg-ues-red hover:bg-red-700 text-white font-bold py-4 px-12 rounded-lg text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition duration-200 flex items-center"
                    >
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                        Iniciar Test
                    </button>
                    
                    <p class="text-center text-sm text-gray-500 mt-4">
                        Al hacer clic en "Iniciar Test" aceptas que has leído y comprendido las instrucciones
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
