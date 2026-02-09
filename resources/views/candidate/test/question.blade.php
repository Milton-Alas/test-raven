@extends('candidate.layouts.app')

@section('title', 'Test en Progreso')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Test de Raven</h1>
                    <p class="text-blue-100">Serie {{ $question->series->code ?? '-' }} · Pregunta {{ $question->question_number ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-100">Tiempo restante</div>
                    <div id="timer" class="text-3xl font-bold">{{ $timerData['remaining_formatted'] ?? '45:00' }}</div>
                </div>
            </div>
        </div>

        <div class="p-8">
            <div class="mb-6">
                <div class="text-sm text-slate-500 mb-2">
                    Progreso: {{ $progress['answered'] ?? 0 }} / {{ $progress['total'] ?? 60 }}
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progress['percentage'] ?? 0 }}%"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-slate-50 rounded-lg p-4 flex items-center justify-center">
                    @if($question?->matrix_image_path)
                        <img src="{{ asset('storage/' . $question->matrix_image_path) }}" alt="Matriz" class="max-w-full h-auto">
                    @else
                        <div class="text-slate-500">Sin imagen de matriz</div>
                    @endif
                </div>

                <form id="answer-form" method="POST" action="{{ route('candidate.test.answer') }}">
                    @csrf
                    <input type="hidden" name="question_id" value="{{ $question->id }}">
                    <input type="hidden" name="elapsed_time" id="elapsed_time" value="{{ $timerData['elapsed_seconds'] ?? 0 }}">

                    <div class="grid grid-cols-2 gap-4">
                        @foreach($question->answerOptions as $option)
                            <label class="border rounded-lg p-3 hover:border-blue-600 cursor-pointer">
                                <input type="radio" name="answer" value="{{ $option->option_number }}" class="mr-2">
                                @if($option->option_image_path)
                                    <img src="{{ asset('storage/' . $option->option_image_path) }}" alt="Opción {{ $option->option_number }}" class="max-w-full h-auto">
                                @else
                                    Opción {{ $option->option_number }}
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg">
                            Enviar respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const timerEl = document.getElementById('timer');
    const elapsedInput = document.getElementById('elapsed_time');

    async function refreshTimer() {
        try {
            const res = await fetch("{{ route('candidate.test.timer') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            timerEl.textContent = data.remaining_formatted;
            elapsedInput.value = data.elapsed_seconds;
            if (data.has_timed_out) {
                window.location.href = "{{ route('candidate.test.completed') }}";
            }
        } catch (e) {
            // silent
        }
    }

    setInterval(refreshTimer, 10000);
</script>
@endsection
