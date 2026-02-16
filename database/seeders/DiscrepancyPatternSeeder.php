<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscrepancyPatternSeeder extends Seeder
{
    public function run(): void
    {
        // Tabla oficial de puntajes esperados por serie según puntaje total
        // Basada en el manual de Raven SPM
        $officialPoints = [
            10 => ['A' => 6, 'B' => 2, 'C' => 1, 'D' => 1, 'E' => 0],
            15 => ['A' => 8, 'B' => 4, 'C' => 2, 'D' => 1, 'E' => 0],
            20 => ['A' => 9, 'B' => 6, 'C' => 3, 'D' => 2, 'E' => 0],
            25 => ['A' => 10, 'B' => 7, 'C' => 4, 'D' => 3, 'E' => 1],
            30 => ['A' => 10, 'B' => 8, 'C' => 7, 'D' => 4, 'E' => 2],
            35 => ['A' => 10, 'B' => 8, 'C' => 8, 'D' => 7, 'E' => 3],
            40 => ['A' => 10, 'B' => 9, 'C' => 8, 'D' => 9, 'E' => 4],
            45 => ['A' => 11, 'B' => 10, 'C' => 10, 'D' => 9, 'E' => 5],
            50 => ['A' => 12, 'B' => 11, 'C' => 10, 'D' => 10, 'E' => 7],
            55 => ['A' => 12, 'B' => 11, 'C' => 11, 'D' => 11, 'E' => 10],
            60 => ['A' => 12, 'B' => 12, 'C' => 12, 'D' => 12, 'E' => 12],
        ];

        $patterns = [];

        // Generar para cada puntaje de 0 a 60
        for ($score = 0; $score <= 60; $score++) {
            if (isset($officialPoints[$score])) {
                // Punto oficial: usar directamente
                $patterns[] = [
                    'total_score' => $score,
                    'expected_a' => $officialPoints[$score]['A'],
                    'expected_b' => $officialPoints[$score]['B'],
                    'expected_c' => $officialPoints[$score]['C'],
                    'expected_d' => $officialPoints[$score]['D'],
                    'expected_e' => $officialPoints[$score]['E'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                // Interpolar linealmente entre puntos conocidos
                $lower = null;
                $upper = null;

                foreach (array_keys($officialPoints) as $knownScore) {
                    if ($knownScore < $score) {
                        $lower = $knownScore;
                    }
                    if ($knownScore > $score && $upper === null) {
                        $upper = $knownScore;
                        break;
                    }
                }

                if ($lower !== null && $upper !== null) {
                    $ratio = ($score - $lower) / ($upper - $lower);

                    $patterns[] = [
                        'total_score' => $score,
                        'expected_a' => (int) round($officialPoints[$lower]['A'] + ($officialPoints[$upper]['A'] - $officialPoints[$lower]['A']) * $ratio),
                        'expected_b' => (int) round($officialPoints[$lower]['B'] + ($officialPoints[$upper]['B'] - $officialPoints[$lower]['B']) * $ratio),
                        'expected_c' => (int) round($officialPoints[$lower]['C'] + ($officialPoints[$upper]['C'] - $officialPoints[$lower]['C']) * $ratio),
                        'expected_d' => (int) round($officialPoints[$lower]['D'] + ($officialPoints[$upper]['D'] - $officialPoints[$lower]['D']) * $ratio),
                        'expected_e' => (int) round($officialPoints[$lower]['E'] + ($officialPoints[$upper]['E'] - $officialPoints[$lower]['E']) * $ratio),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } elseif ($score < 10) {
                    // Puntajes < 10: estimación proporcional (no hay datos oficiales)
                    $patterns[] = [
                        'total_score' => $score,
                        'expected_a' => min(6, (int) round($score * 0.6)),
                        'expected_b' => min(2, (int) round($score * 0.2)),
                        'expected_c' => min(1, (int) round($score * 0.1)),
                        'expected_d' => min(1, (int) round($score * 0.1)),
                        'expected_e' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        DB::table('discrepancy_patterns')->insert($patterns);
    }
}
