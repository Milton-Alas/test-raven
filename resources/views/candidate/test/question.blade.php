@extends('candidate.layouts.app')

@section('title', 'Test en Progreso')

@php
    $seriesCode = $question->series->code;
    $numOptions = in_array($seriesCode, ['C', 'D', 'E']) ? 8 : 6;
    // 6 opciones (A, B) = 3 columnas (2 filas de 3)
    // 8 opciones (C, D, E) = 4 columnas (2 filas de 4)
    $gridColsClass = ($numOptions === 8) ? 'lg:grid-cols-4' : 'lg:grid-cols-3';
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <!-- Header con información del test -->
        <div class="bg-gradient-to-r from-ues-blue to-blue-700 px-8 py-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Test de Raven</h1>
                    <p class="text-blue-100 mt-1">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            Serie {{ $question->series->code ?? '-' }} · Pregunta {{ $question->question_number ?? '-' }}
                        </span>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-100 mb-1">Tiempo restante</div>
                    <div id="timer" class="text-3xl font-bold tabular-nums">{{ $timerData['remaining_formatted'] ?? '45:00' }}</div>
                </div>
            </div>
        </div>

        <div class="p-8">
            <!-- Barra de progreso -->
            <div class="mb-8">
                <div class="flex items-center justify-between text-sm text-slate-600 mb-2">
                    <span class="font-medium">Progreso del test</span>
                    <span class="font-semibold text-ues-blue">{{ $progress['answered'] ?? 0 }} / {{ $progress['total'] ?? 60 }} preguntas</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-ues-blue to-blue-600 h-3 rounded-full transition-all duration-500 ease-out" 
                         style="width: {{ $progress['percentage'] ?? 0 }}%"></div>
                </div>
            </div>

            <!-- Imagen de la matriz (ARRIBA) -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-ues-blue mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Matriz del problema
                </h3>
                <div class="bg-slate-50 rounded-xl p-6 border-2 border-slate-200 flex items-center justify-center">
                    @if($question?->matrix_image_path)
                        <img src="{{ asset('storage/' . $question->matrix_image_path) }}" 
                             alt="Matriz" 
                             class="max-w-full h-auto rounded-lg shadow-sm">
                    @else
                        <div class="text-slate-400 text-center py-12">
                            <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p>Sin imagen de matriz</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Formulario de respuestas (ABAJO EN 2 FILAS) -->
            <div>
                <h3 class="text-lg font-semibold text-ues-blue mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Selecciona tu respuesta
                </h3>

                <form id="answer-form" method="POST" action="{{ route('candidate.test.answer') }}">
                    @csrf
                    <input type="hidden" name="question_id" value="{{ $question->id }}">
                    <input type="hidden" name="elapsed_time" id="elapsed_time" value="{{ $timerData['elapsed_seconds'] ?? 0 }}">

                    <!-- Grid: 2 columnas móvil, 3 columnas (series A,B) o 4 columnas (series C,D,E) en desktop -->
                    <div class="grid grid-cols-2 {{ $gridColsClass }} gap-4 mb-6">
                        @foreach($question->answerOptions->take($numOptions) as $option)
                            <label class="border-2 border-slate-300 rounded-lg p-4 hover:border-ues-blue hover:shadow-md cursor-pointer transition-all duration-200 option-card flex flex-col items-center">
                                <!-- Radio button visible -->
                                <div class="flex items-center justify-between w-full mb-3">
                                    <span class="text-sm font-semibold text-slate-700">Opción {{ $option->option_number }}</span>
                                    <input type="radio" 
                                           name="answer" 
                                           value="{{ $option->option_number }}" 
                                           class="w-5 h-5 text-ues-blue border-slate-300 focus:ring-2 focus:ring-ues-blue option-radio">
                                </div>
                                
                                <!-- Imagen de la opción -->
                                <div class="flex items-center justify-center min-h-[100px] w-full">
                                    @if($option->option_image_path)
                                        <img src="{{ asset('storage/' . $option->option_image_path) }}" 
                                             alt="Opción {{ $option->option_number }}" 
                                             class="max-w-full h-auto">
                                    @else
                                        <span class="text-slate-400 text-sm">Imagen {{ $option->option_number }}</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <!-- Botón de envío -->
                    <div class="flex flex-col sm:flex-row items-center justify-between mt-8 pt-6 border-t border-slate-200 gap-4">
                        <p class="text-sm text-slate-500 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            Recuerda: no podrás regresar
                        </p>
                        <button type="submit" 
                                class="bg-ues-red hover:bg-red-700 text-white font-semibold py-3 px-8 rounded-lg 
                                       shadow-md hover:shadow-lg transform hover:scale-105 transition duration-200
                                       flex items-center">
                            <span>Enviar respuesta</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para resaltar la opción seleccionada */
.option-card:has(input:checked) {
    border-color: #0047AB !important;
    background-color: #EFF6FF !important;
    box-shadow: 0 10px 15px -3px rgba(0, 71, 171, 0.2), 0 4px 6px -2px rgba(0, 71, 171, 0.1) !important;
    transform: scale(1.03);
}

.option-card:has(input:checked)::before {
    content: "✓ SELECCIONADA";
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #0047AB;
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 4px 12px;
    border-radius: 9999px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 10;
    white-space: nowrap;
}

.option-card {
    position: relative;
}

/* Forzar el grid en desktop */
@media (min-width: 1024px) {
    .grid.lg\:grid-cols-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }
    .grid.lg\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }
}
</style>

<script>
    const timerEl = document.getElementById('timer');
    const elapsedInput = document.getElementById('elapsed_time');

    async function refreshTimer() {
        try {
            const res = await fetch("{{ route('candidate.test.timer') }}", { 
                headers: { 'X-Requested-With': 'XMLHttpRequest' } 
            });
            if (!res.ok) return;
            const data = await res.json();
            
            // Actualizar timer con animación
            timerEl.textContent = data.remaining_formatted;
            elapsedInput.value = data.elapsed_seconds;
            
            // Añadir clase de advertencia si quedan menos de 5 minutos
            if (data.elapsed_seconds > 2400) { // 40 minutos transcurridos
                timerEl.classList.add('text-ues-red', 'animate-pulse');
            }
            
            if (data.has_timed_out) {
                window.location.href = "{{ route('candidate.test.completed') }}";
            }
        } catch (e) {
            // silent
        }
    }

    // Actualizar cada 10 segundos //esto se debe modificar 
    setInterval(refreshTimer, 10000);
    
    // Prevenir cierre accidental de la ventana
    const beforeUnloadHandler = function (e) {
        e.preventDefault();
        e.returnValue = '';
    };
    window.addEventListener('beforeunload', beforeUnloadHandler);
    
    // Enviar respuesta vía AJAX y avanzar a la siguiente pregunta
    document.getElementById('answer-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = e.currentTarget;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });

            const data = await res.json().catch(() => null);

            if (!res.ok || !data?.success) {
                const message = data?.message || 'No se pudo guardar la respuesta.';
                alert(message);
                return;
            }

            // Permitir navegación sin prompt antes de redirigir
            window.removeEventListener('beforeunload', beforeUnloadHandler);

            if (data.completed && data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            // Avanzar a la siguiente pregunta
            window.location.href = "{{ route('candidate.test.question') }}";
        } catch (e) {
            alert('Error al guardar la respuesta.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    });
</script>
@endsection