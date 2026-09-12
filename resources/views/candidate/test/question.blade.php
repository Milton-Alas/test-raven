@extends('candidate.layouts.app')

@section('title', 'Test en Progreso')

@php
    $seriesCode = $question->series->code;
    $numOptions = in_array($seriesCode, ['C', 'D', 'E']) ? 8 : 6;
    // Clases de Grid para Bootstrap
    $gridColsClass = ($numOptions === 8) ? 'row-cols-2 row-cols-lg-4' : 'row-cols-2 row-cols-lg-3';
@endphp

@section('content')
<div class="container-fluid px-3 py-4 min-vh-100 d-flex flex-column justify-content-between">
    <!-- Header con Timer y Progreso -->
    <div class="card shadow-sm border-0 rounded-4 mb-4 border-top border-4 border-ues-blue">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-4">
                
                <!-- Progreso -->
                <div class="flex-grow-1 w-100">
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="bg-ues-blue text-white rounded-3 px-4 py-2 shadow-sm text-center">
                            <span class="d-block small opacity-75 mb-1">Pregunta</span>
                            <div class="fs-3 fw-bold lh-1">
                                {{ $progress['current_display'] }}<span class="fs-6 opacity-75">/60</span>
                            </div>
                        </div>
                        
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small fw-bold text-ues-blue">Serie {{ $question->series->code }}</span>
                                <span class="small text-muted">({{ $question->question_number }}/12)</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-ues-blue progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: {{ $progress['percentage'] }}%" 
                                     aria-valuenow="{{ $progress['percentage'] }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timer -->
                <div class="text-center ms-lg-4">
                    <span class="small text-muted fw-bold d-block mb-2">Tiempo Restante</span>
                    <div id="timer-display" 
                         class="bg-light border border-2 rounded-3 px-4 py-2 fs-3 fw-bold text-dark font-monospace shadow-sm"
                         style="min-width: 150px;"
                         data-expires-at="{{ $timerData['expires_at'] }}"
                    data-remaining-seconds="{{ $timerData['remaining_seconds'] }}">
                        {{ $timerData['remaining_formatted'] }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Pregunta principal -->
    <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4">
        
        <!-- Matriz Principal -->
        <div class="bg-light py-4 px-2 border-bottom">
            <h2 class="h6 text-center text-ues-blue fw-bold mb-4">
                <i class="bi bi-puzzle-fill me-2"></i> Selecciona la opción que completa correctamente la matriz
            </h2>
            
            <div class="bg-white rounded-3 shadow-sm border p-3 mx-auto d-flex justify-content-center align-items-center" style="max-width: 600px;">
                <img src="{{ asset('storage/' . $question->matrix_image_path) }}" 
                     alt="Matriz {{ $question->full_code }}"
                     class="img-fluid"
                     style="max-height: 350px;">
            </div>
        </div>

        <!-- Opciones de Respuesta -->
        <div class="card-body py-4 px-3">
            <h3 class="h6 text-center text-ues-blue fw-bold mb-4">
                <i class="bi bi-ui-radios-grid me-2"></i> Opciones de Respuesta
            </h3>

            <!-- Grid de opciones -->
            <div class="row g-3 g-md-4 mx-auto {{ $gridColsClass }}" id="options-grid" style="max-width: 900px;">
                @foreach($question->answerOptions->take($numOptions) as $option)
                    <div class="col">
                        <div class="card option-card h-100 border-2 cursor-pointer transition-hover" data-option="{{ $option->option_number }}">
                            <div class="card-body p-2 d-flex justify-content-center align-items-center position-relative bg-light rounded" style="min-height: 110px;">
                                
                                <!-- Número de opción -->
                                <span class="option-number position-absolute top-0 start-0 translate-middle badge rounded-circle bg-dark shadow-sm d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem; z-index: 10;">
                                    {{ $option->option_number }}
                                </span>
                                
                                <!-- Imagen pequeña -->
                                <img src="{{ asset('storage/' . $option->option_image_path) }}" 
                                     alt="Opción {{ $option->option_number }}"
                                     class="img-fluid object-fit-contain"
                                     style="max-height: 90px;">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Botón Siguiente -->
            <div class="mt-4 d-flex flex-column align-items-center">
                <button id="next-btn"
                        disabled
                        class="btn btn-ues-rojo btn-lg px-5 py-3 fw-bold shadow-sm rounded-3 d-flex align-items-center transform-hover disabled-opacity">
                    <span id="btn-text">Selecciona una opción</span>
                    
                    <i id="btn-arrow" class="bi bi-arrow-right-circle-fill ms-2 fs-5 d-none"></i>
                    
                    <div id="btn-loading" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </button>
                
                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-exclamation-circle-fill text-warning me-1"></i> Recuerda: no podrás regresar a esta pregunta
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-none align-items-center justify-content-center" style="z-index: 1055; backdrop-filter: blur(3px);">
    <div class="bg-white rounded-4 p-5 shadow-lg text-center mx-3" style="max-width: 350px;">
        <div class="text-ues-blue mb-3">
            <div class="spinner-border" style="width: 3.5rem; height: 3.5rem; border-width: 0.25em;" role="status"></div>
        </div>
        <h4 class="h5 fw-bold text-dark mb-1">Guardando respuesta...</h4>
        <p class="text-muted small mb-0">Por favor espera</p>
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
/* Utilidades personalizadas */
.cursor-pointer { cursor: pointer; }
.transition-hover { transition: all 0.2s ease-in-out; }
.transition-hover:hover { border-color: #0047AB; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.disabled-opacity:disabled { opacity: 0.6; cursor: not-allowed; }
.transform-hover:not(:disabled):hover { transform: translateY(-2px); }

/* Estilos para las opciones seleccionadas */
.option-card.selected {
    border-color: #0047AB !important; /* ues-blue */
    transform: scale(1.03);
    box-shadow: 0 0.5rem 1rem rgba(0, 71, 171, 0.15) !important;
}

.option-card.selected .card-body {
    background-color: #e6f0ff !important; /* light-blue */
}

/* Marca de verificación (Check) */
.option-card.selected::after {
    content: "\F26A"; /* Código unicode del icono bi-check-circle-fill de Bootstrap */
    font-family: "bootstrap-icons";
    position: absolute;
    top: -12px;
    right: -12px;
    color: #0047AB; /* ues-blue */
    background: white;
    border-radius: 50%;
    font-size: 24px;
    line-height: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 20;
}

/* Cambiar color del número cuando se selecciona */
.option-card.selected .option-number {
    background-color: #FFCC00 !important; /* ues-yellow */
    color: #212529 !important; /* text-dark */
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/test-timer.js') }}"></script>
@endpush