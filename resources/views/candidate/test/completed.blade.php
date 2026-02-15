@extends('candidate.layouts.app')

@section('title', 'Test Completado')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <!-- Success Header -->
        <div class="bg-gradient-to-r from-ues-blue to-blue-700 px-8 py-12 text-center relative overflow-hidden">
            <!-- Decoración de fondo -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-40 h-40 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-60 h-60 bg-white rounded-full translate-x-1/3 translate-y-1/3"></div>
            </div>
            
            <div class="relative z-10">
                <div class="w-24 h-24 bg-white rounded-full mx-auto mb-6 flex items-center justify-center shadow-lg">
                    <svg class="w-14 h-14 text-ues-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-white mb-3">
                    ¡Test Completado!
                </h1>
                <p class="text-blue-100 text-lg">
                    Has finalizado exitosamente el Test de Matrices Progresivas de Raven
                </p>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            @if($result)
                <!-- Resultados -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6 text-center flex items-center justify-center">
                        <svg class="w-7 h-7 mr-2 text-ues-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Resumen de Resultados
                    </h2>

                    <!-- Puntaje Total -->
                    <div class="bg-gradient-to-br from-ues-blue to-blue-700 rounded-xl p-8 mb-6 text-center shadow-lg relative overflow-hidden">
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full translate-x-1/2 -translate-y-1/2"></div>
                            <div class="absolute bottom-0 left-0 w-40 h-40 bg-white rounded-full -translate-x-1/2 translate-y-1/2"></div>
                        </div>
                        <div class="relative z-10">
                            <p class="text-sm text-blue-100 mb-2 font-semibold uppercase tracking-wider">Puntaje Total</p>
                            <p class="text-7xl font-bold text-white mb-2">
                                {{ $result->total_score }}
                            </p>
                            <p class="text-2xl text-blue-100">de 60 puntos</p>
                        </div>
                    </div>

                    <!-- Estadísticas principales -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-5 text-center border-2 border-blue-200">
                            <div class="w-12 h-12 bg-ues-blue rounded-full mx-auto mb-3 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <p class="text-xs text-slate-600 mb-1 font-semibold uppercase tracking-wide">Percentil</p>
                            <p class="text-3xl font-bold text-ues-blue">{{ $result->percentile }}</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-5 text-center border-2 border-blue-200">
                            <div class="w-12 h-12 bg-ues-blue rounded-full mx-auto mb-3 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <p class="text-xs text-slate-600 mb-1 font-semibold uppercase tracking-wide">Rango</p>
                            <p class="text-2xl font-bold text-ues-blue">{{ $result->diagnostic_range }}</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-5 text-center border-2 border-blue-200">
                            <div class="w-12 h-12 bg-ues-blue rounded-full mx-auto mb-3 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-xs text-slate-600 mb-1 font-semibold uppercase tracking-wide">Tiempo Total</p>
                            <p class="text-2xl font-bold text-ues-blue">{{ gmdate('i:s', $result->total_time_seconds) }}</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-5 text-center border-2 border-blue-200">
                            <div class="w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center {{ $result->is_valid ? 'bg-green-500' : 'bg-ues-red' }}">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($result->is_valid)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    @endif
                                </svg>
                            </div>
                            <p class="text-xs text-slate-600 mb-1 font-semibold uppercase tracking-wide">Estado</p>
                            <p class="text-lg font-bold {{ $result->is_valid ? 'text-green-600' : 'text-ues-red' }}">
                                {{ $result->is_valid ? 'Válido' : 'Inválido' }}
                            </p>
                        </div>
                    </div>

                    <!-- Clasificación -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-ues-blue rounded-lg p-6 mb-6 shadow-sm">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-ues-blue" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="font-bold text-ues-blue mb-1">Clasificación Diagnóstica</h3>
                                <p class="text-slate-700 text-lg font-semibold">
                                    {{ $result->diagnostic_label }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Puntajes por Serie -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-slate-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-ues-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Puntajes por Serie
                        </h3>
                        <div class="grid grid-cols-5 gap-3">
                            @foreach(['A', 'B', 'C', 'D', 'E'] as $serie)
                                <div class="bg-slate-50 rounded-lg p-4 text-center border-2 border-slate-200 hover:border-ues-blue transition-colors">
                                    <div class="w-10 h-10 bg-ues-gold rounded-full mx-auto mb-2 flex items-center justify-center">
                                        <span class="text-sm font-bold text-slate-800">{{ $serie }}</span>
                                    </div>
                                    <p class="text-xs text-slate-600 mb-1 font-medium">Serie {{ $serie }}</p>
                                    <p class="text-2xl font-bold text-slate-900">
                                        {{ $result->{'series_' . strtolower($serie) . '_score'} }}
                                    </p>
                                    <p class="text-xs text-slate-500">/12</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if(!$result->is_valid)
                        <div class="bg-yellow-50 border-l-4 border-ues-gold rounded-lg p-5 shadow-sm">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-ues-gold" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-slate-800">
                                        <strong class="font-semibold">Nota:</strong> {{ $result->validity_notes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Mensaje Final -->
            <div class="bg-slate-50 rounded-lg p-6 mb-6 border-2 border-slate-200">
                <h3 class="font-semibold text-slate-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-ues-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    ¿Qué sigue?
                </h3>
                <p class="text-slate-700 mb-3">
                    Tus resultados han sido registrados exitosamente. Un administrador revisará tu evaluación 
                    y podrás obtener un reporte detallado de tu desempeño.
                </p>
                <p class="text-slate-700">
                    Gracias por completar el Test de Matrices Progresivas de Raven.
                </p>
            </div>

            <!-- Información de Contacto -->
            <div class="text-center">
                <p class="text-sm text-slate-600 mb-6">
                    Si tienes alguna pregunta sobre tus resultados, por favor contacta al administrador.
                </p>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button 
                        type="submit"
                        class="bg-slate-600 hover:bg-slate-700 text-white font-semibold py-3 px-10 rounded-lg transition duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
                    >
                        <span class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Cerrar Sesión
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection