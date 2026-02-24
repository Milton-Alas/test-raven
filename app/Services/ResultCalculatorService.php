<?php

namespace App\Services;

use App\Models\DiagnosticRange;
use App\Models\DiscrepancyPattern;
use App\Models\PercentileTable;
use App\Models\TestAnswer;
use App\Models\TestResult;
use App\Models\TestSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultCalculatorService
{
    /**
     * Calcular todos los resultados del test
     */
    public function calculateResult(TestSession $session): ?TestResult
    {
        // CORRECCIÓN: Asegurar que la sesión esté finalizada (por tiempo o completitud)
        if (!in_array($session->status, ['completed', 'timeout'])) {
            Log::warning('Se intentó calcular el resultado para una sesión no finalizada.', [
                'session_id' => $session->id,
                'status' => $session->status,
            ]);
            return null;
        }

        DB::beginTransaction();

        try {
            $candidate = $session->candidate;

            // 1. Calcular puntajes por serie
            $scores = $this->calculateSeriesScores($session);
            $totalScore = array_sum($scores);

            // 2. Buscar percentil según edad (tabla separada)
            $percentile = $this->findPercentile($totalScore, (int) $candidate->age);

            // 3. Buscar diagnóstico según percentil (tabla separada)
            $diagnostic = $this->findDiagnostic($percentile);

            // 4. Validar discrepancia por serie (tabla exacta)
            $discrepancyCheck = $this->validateDiscrepancy($totalScore, $scores);

            // 5. Calcular tiempos
            $timeData = $this->calculateTimes($session);

            // 6. Crear/actualizar resultado
            $result = TestResult::updateOrCreate(
                ['test_session_id' => $session->id],
                [
                    'candidate_id' => $candidate->id,
                    'series_a_score' => $scores['A'],
                    'series_b_score' => $scores['B'],
                    'series_c_score' => $scores['C'],
                    'series_d_score' => $scores['D'],
                    'series_e_score' => $scores['E'],
                    'total_score' => $totalScore,
                    'percentile' => $percentile,
                    'diagnostic_range' => $diagnostic['range_number'],
                    'diagnostic_label' => $diagnostic['diagnostic_label'],
                    'is_valid' => $discrepancyCheck['is_valid'],
                    'validity_notes' => $discrepancyCheck['notes'] ?? '',
                    'total_time_seconds' => $timeData['total_seconds'],
                    'average_time_per_question' => $timeData['average_per_question'],
                    'score_distribution' => $this->getScoreDistribution($session),
                    'calculated_at' => now(),
                ]
            );

            DB::commit();

            Log::info('Resultados calculados', [
                'session_id' => $session->id,
                'total_score' => $totalScore,
                'percentile' => $percentile,
                'diagnostic' => $diagnostic['diagnostic_label'],
                'is_valid' => $discrepancyCheck['is_valid'],
            ]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al calcular resultados', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcular puntajes por serie
     */
    private function calculateSeriesScores(TestSession $session): array
    {
        $scores = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
            'D' => 0,
            'E' => 0,
        ];

        $answers = TestAnswer::where('test_session_id', $session->id)
            ->with('testQuestion.series')
            ->get();

        foreach ($answers as $answer) {
            if ($answer->is_correct && $answer->testQuestion?->series?->code) {
                $seriesCode = $answer->testQuestion->series->code;
                $scores[$seriesCode] = ($scores[$seriesCode] ?? 0) + 1;
            }
        }

        return $scores;
    }

    /**
     * Buscar percentil en tabla normalizada (solo percentiles)
     */
    private function findPercentile(int $rawScore, int $age, string $normGroup = 'montevideo'): int
    {
        // Buscar puntaje exacto para la edad
        $record = PercentileTable::active()
            ->forAge($age)
            ->forNormGroup($normGroup)
            ->forScore($rawScore)
            ->first();

        // Si no hay puntaje exacto, buscar el más cercano inferior
        if (!$record) {
            $record = PercentileTable::active()
                ->forAge($age)
                ->forNormGroup($normGroup)
                ->where('raw_score', '<=', $rawScore)
                ->orderBy('raw_score', 'desc')
                ->first();
        }

        if (!$record) {
            Log::warning("No se encontró percentil para edad {$age} y puntaje {$rawScore}");
            return 50; // Default: percentil 50 (promedio)
        }

        return $record->percentile;
    }

    /**
     * Buscar diagnóstico según percentil (tabla separada)
     */
    private function findDiagnostic(int $percentile): array
    {
        $record = DiagnosticRange::forPercentile($percentile)->first();

        if (!$record) {
            return [
                'range_number' => 3,
                'range_label' => 'III',
                'diagnostic_label' => 'Término Medio',
                'interpretation' => 'Sin clasificación disponible.',
            ];
        }

        return [
            'range_number' => $record->range_number,
            'range_label' => $record->range_label,
            'diagnostic_label' => $record->diagnostic_label,
            'interpretation' => $record->interpretation,
        ];
    }

    /**
     * Validar discrepancia por serie según criterio oficial Raven (±2 por serie)
     */
    private function validateDiscrepancy(int $totalScore, array $seriesScores): array
    {
        $pattern = DiscrepancyPattern::forScore($totalScore)->first();

        if (!$pattern) {
            return [
                'is_valid' => false,
                'notes' => "Puntaje total {$totalScore} no tiene patrón de referencia en tabla de discrepancia.",
                'discrepancies' => [],
            ];
        }

        $expected = $pattern->expected_array;
        $discrepancies = [];
        $isValid = true;

        // CRITERIO OFICIAL: Ninguna serie puede diferir más de ±2 del esperado
        foreach (['A', 'B', 'C', 'D', 'E'] as $serie) {
            $actual = $seriesScores[$serie];
            $exp = $expected[$serie];
            $diff = $actual - $exp;

            if (abs($diff) > 2) {
                $isValid = false;
                $discrepancies[] = "Serie {$serie}: esperado {$exp}, obtuvo {$actual} (diferencia: {$diff})";
            }
        }

        return [
            'is_valid' => $isValid,
            'notes' => !empty($discrepancies)
                ? 'Patrón de respuestas inconsistente con puntaje total. ' . implode('; ', $discrepancies)
                : null,
            'discrepancies' => $discrepancies,
            'expected' => $expected,
        ];
    }

    /**
     * Calcular datos de tiempo
     */
    private function calculateTimes(TestSession $session): array
    {
        $totalSeconds = $session->elapsed_time ?? 0;
        $answeredCount = TestAnswer::where('test_session_id', $session->id)->count();
        $averagePerQuestion = $answeredCount > 0
            ? (int) round($totalSeconds / $answeredCount)
            : 0;

        return [
            'total_seconds' => $totalSeconds,
            'average_per_question' => $averagePerQuestion,
        ];
    }

    /**
     * Obtener distribución detallada de puntajes
     */
    private function getScoreDistribution(TestSession $session): array
    {
        $answers = TestAnswer::where('test_session_id', $session->id)
            ->with('testQuestion.series')
            ->get();

        $distribution = [
            'total_questions' => 60,
            'answered' => $answers->count(),
            'correct' => $answers->where('is_correct', true)->count(),
            'incorrect' => $answers->where('is_correct', false)->count(),
            'by_series' => [],
        ];

        foreach (['A', 'B', 'C', 'D', 'E'] as $seriesCode) {
            $seriesAnswers = $answers->filter(function ($answer) use ($seriesCode) {
                return $answer->testQuestion?->series?->code === $seriesCode;
            });

            $distribution['by_series'][$seriesCode] = [
                'total' => 12,
                'answered' => $seriesAnswers->count(),
                'correct' => $seriesAnswers->where('is_correct', true)->count(),
                'incorrect' => $seriesAnswers->where('is_correct', false)->count(),
            ];
        }

        return $distribution;
    }

    /**
     * Obtener interpretación textual del rango diagnóstico
     */
    public function getDiagnosticInterpretation(int $diagnosticRange): string
    {
        return match ($diagnosticRange) {
            1 => 'Rango I - Intelectualmente Superior: Capacidad intelectual superior al 95% de la población de su edad.',
            2 => 'Rango II - Superior al Término Medio: Capacidad intelectual definitivamente superior al promedio.',
            3 => 'Rango III - Término Medio: Capacidad intelectual dentro del promedio esperado para su edad.',
            4 => 'Rango IV - Inferior al Término Medio: Capacidad intelectual definitivamente por debajo del promedio.',
            5 => 'Rango V - Intelectualmente Deficiente: Capacidad intelectual significativamente por debajo del promedio.',
            default => 'Sin clasificación',
        };
    }
}
