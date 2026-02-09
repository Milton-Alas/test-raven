<?php

namespace App\Services;

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
    public function calculateResult(TestSession $session): TestResult
    {
        DB::beginTransaction();

        try {
            $candidate = $session->candidate;

            $scores = $this->calculateSeriesScores($session);
            $totalScore = array_sum($scores);

            $percentileData = $this->findPercentile($totalScore, (int) $candidate->age);
            $discrepancy = $this->calculateDiscrepancy(
                $totalScore,
                (int) $percentileData['equivalent_score']
            );
            $isValid = $this->validateResults($discrepancy);
            $timeData = $this->calculateTimes($session);

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
                    'percentile' => $percentileData['percentile'],
                    'diagnostic_range' => $percentileData['diagnostic_range'],
                    'diagnostic_label' => $percentileData['diagnostic_label'],
                    'expected_score' => $percentileData['equivalent_score'],
                    'discrepancy' => $discrepancy,
                    'is_valid' => $isValid,
                    'validity_notes' => $isValid ? null : 'Discrepancia fuera del rango aceptable (-2 a +2)',
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
                'percentile' => $percentileData['percentile'],
                'is_valid' => $isValid,
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
     * Buscar percentil en tabla de baremos según edad y puntaje
     */
    private function findPercentile(int $rawScore, int $age, string $normGroup = 'general'): array
    {
        $percentileRecord = PercentileTable::query()
            ->forAgeAndScore($age, $rawScore, $normGroup)
            ->first();

        if (!$percentileRecord) {
            $percentileRecord = PercentileTable::query()
                ->forAge($age)
                ->forNormGroup($normGroup)
                ->active()
                ->where('raw_score', '<=', $rawScore)
                ->orderBy('raw_score', 'desc')
                ->first();
        }

        if (!$percentileRecord) {
            Log::warning("No se encontró percentil para edad {$age} y puntaje {$rawScore}");

            return [
                'percentile' => 50,
                'diagnostic_range' => 3,
                'diagnostic_label' => 'Término Medio',
                'equivalent_score' => $rawScore,
            ];
        }

        return [
            'percentile' => $percentileRecord->percentile,
            'diagnostic_range' => $percentileRecord->diagnostic_range,
            'diagnostic_label' => $percentileRecord->diagnostic_label,
            'equivalent_score' => $percentileRecord->equivalent_score,
        ];
    }

    /**
     * Calcular discrepancia (PS - PE)
     */
    private function calculateDiscrepancy(int $sumScore, int $equivalentScore): float
    {
        return $sumScore - $equivalentScore;
    }

    /**
     * Validar si los resultados son confiables
     */
    private function validateResults(float $discrepancy): bool
    {
        return $discrepancy >= -2 && $discrepancy <= 2;
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
