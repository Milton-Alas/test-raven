@extends('candidate.layouts.app')

@section('title', 'Test en Progreso')

@php
    $seriesCode = $question->series->code;
    $numOptions = in_array($seriesCode, ['C', 'D', 'E']) ? 8 : 6;
    $gridColsClass = ($numOptions === 8) ? 'lg:grid-cols-4' : 'lg:grid-cols-3';
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header con Timer y Progreso -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-6 border-t-4 border-ues-blue">
        <div class="flex flex-col lg:flex-row justify-between items-center gap-6">
            <!-- Progreso -->
            <div class="flex-1 w-full">
                <div class="flex items-center gap-6">
                    <div class="bg-gradient-to-br from-ues-blue to-blue-700 rounded-lg px-5 py-3 text-white shadow-md">
                        <p class="text-xs text-blue-100 mb-1">Pregunta</p>
                        <p class="text-2xl font-bold">
                            {{ $progress['current_display'] }}
                            <span class="text-sm text-blue-100">/60</span>
                        </p>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-ues-blue">Serie {{ $question->series->code }}</span>
                            <span class="text-xs text-slate-500">({{ $question->question_number }}/12)</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden shadow-inner">
                            <div 
                                class="bg-gradient-to-r from-ues-blue to-blue-600 h-3 rounded-full transition-all duration-500 ease-out"
                                style="width: {{ $progress['percentage'] }}%"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timer -->
            <div class="lg:ml-8">
                <p class="text-xs text-slate-600 text-center mb-1 font-semibold">Tiempo Restante</p>
                <div 
                    id="timer-display" 
                    class="text-3xl font-bold text-center px-6 py-2 rounded-lg shadow-md bg-slate-50 border-2 border-slate-300 tabular-nums min-w-[140px]"
                    data-expires-at="{{ $timerData['expires_at'] }}"
                    data-remaining-seconds="{{ $timerData['remaining_seconds'] }}"
                >
                    {{ $timerData['remaining_formatted'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Pregunta -->
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <!-- Matriz Principal -->
        <div class="bg-gradient-to-b from-slate-50 to-white p-8">
            <h2 class="text-center text-base font-semibold text-ues-blue mb-4 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Selecciona la opción que completa correctamente la matriz
            </h2>
            
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-4 border-2 border-slate-200 flex items-center justify-center">
                <img 
                    src="{{ asset('storage/' . $question->matrix_image_path) }}" 
                    alt="Matriz {{ $question->full_code }}"
                    class="max-w-full h-auto"
                    style="max-height: 350px;"
                >
            </div>
        </div>

        <!-- Opciones de Respuesta -->
        <div class="p-8 bg-white">
            <h3 class="text-center text-base font-semibold text-ues-blue mb-4 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Opciones de Respuesta
            </h3>

            <div class="grid grid-cols-2 {{ $gridColsClass }} gap-4 max-w-4xl mx-auto" id="options-grid">
                @foreach($question->answerOptions->take($numOptions) as $option)
                    <div 
                        class="option-card cursor-pointer bg-white border-2 border-slate-300 rounded-lg p-3 hover:border-ues-blue hover:shadow-md transition-all duration-200 relative"
                        data-option="{{ $option->option_number }}"
                    >
                        <div class="relative">
                            <!-- Número de opción -->
                            <div class="option-number absolute -top-2 -left-2 bg-slate-700 text-white w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shadow-md z-10">
                                {{ $option->option_number }}
                            </div>
                            
                            <!-- Imagen pequeña -->
                            <div class="flex items-center justify-center bg-slate-50 rounded p-2" style="min-height: 80px; max-height: 120px;">
                                <img 
                                    src="{{ asset('storage/' . $option->option_image_path) }}" 
                                    alt="Opción {{ $option->option_number }}"
                                    class="max-w-full h-auto object-contain"
                                    style="max-height: 100px;"
                                >
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Botón Siguiente -->
            <div class="mt-8 flex flex-col items-center gap-3">
                <button 
                    id="next-btn"
                    disabled
                    class="disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-400 
                           bg-ues-red hover:bg-red-700 text-white font-bold py-3 px-10 rounded-lg text-base
                           shadow-md hover:shadow-lg transition duration-200 transform hover:scale-105
                           flex items-center"
                >
                    <span id="btn-text">Selecciona una opción</span>
                    <svg id="btn-arrow" class="hidden w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                    <svg id="btn-loading" class="hidden animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                
                <p class="text-xs text-slate-500 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    Recuerda: no podrás regresar a esta pregunta
                </p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-8 max-w-sm shadow-2xl">
            <div class="text-center">
                <div class="w-16 h-16 bg-ues-blue rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="animate-spin h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <p class="text-slate-900 font-semibold text-lg">Guardando respuesta...</p>
                <p class="text-slate-500 text-sm mt-2">Por favor espera</p>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Data -->
<input type="hidden" id="question-id" value="{{ $question->id }}">
<input type="hidden" id="session-id" value="{{ $session->id }}">
<input type="hidden" id="csrf-token" value="{{ csrf_token() }}">
<input type="hidden" id="save-answer-url" value="{{ route('candidate.test.answer') }}">
<input type="hidden" id="timer-url" value="{{ route('candidate.test.timer') }}">
<input type="hidden" id="timeout-url" value="{{ route('candidate.test.timeout') }}">
<input type="hidden" id="completed-url" value="{{ route('candidate.test.completed') }}">
@endsection

@push('styles')
<style>
/* Estilos para las opciones seleccionadas */
.option-card.selected {
    border-color: #0047AB !important; /* ues-blue */
    background-color: #EFF6FF !important; /* blue-50 */
    box-shadow: 0 4px 6px -1px rgba(0, 71, 171, 0.2), 0 2px 4px -1px rgba(0, 71, 171, 0.1) !important;
    transform: scale(1.05);
}

.option-card.selected::after {
    content: "✓";
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #0047AB; /* ues-blue */
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    z-index: 20;
}

.option-card.selected .option-number {
    background-color: #FFCC00 !important; /* ues-yellow */
    color: #1F2937 !important; /* gray-800 */
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/test-timer.js') }}"></script>
@endpush
