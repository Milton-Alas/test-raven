<?php

namespace Database\Seeders;

use App\Models\PercentileTable;
use Illuminate\Database\Seeder;

class PercentileTableSeeder extends Seeder
{
    public function run(): void
    {
        // Baremo de Montevideo - Adolescente y Adultos
        // Fuente: Tabla VII del manual Raven SPM
        // Nota: P5 y P95 estimados por interpolación entre puntos adyacentes
        // P95 para 22-65 = 58 (dato explícito del manual)

        $ageGroups = [
            ['min' => 12, 'max' => 12],
            ['min' => 13, 'max' => 14],
            ['min' => 15, 'max' => 16],
            ['min' => 17, 'max' => 17],
            ['min' => 18, 'max' => 18],
            ['min' => 19, 'max' => 19],
            ['min' => 20, 'max' => 21],
            ['min' => 22, 'max' => 65],
        ];

        // Datos oficiales del Baremo: percentil => [puntajes por grupo de edad]
        // Orden de grupos: 12, 13-14, 15-16, 17, 18, 19, 20-21, 22-65
        $baremo = [
            99 => [53, 54, 55, 56, 57, 57, 58, 59],
            95 => [50, 52, 53, 54, 55, 56, 56, 58],  // Estimado excepto 22-65 (oficial)
            90 => [47, 49, 50, 52, 53, 54, 54, 55],
            75 => [43, 45, 46, 49, 50, 51, 51, 52],
            50 => [39, 40, 41, 45, 46, 47, 47, 48],
            25 => [33, 34, 35, 39, 42, 42, 43, 44],
            10 => [24, 27, 29, 35, 36, 37, 37, 38],
            5  => [19, 22, 24, 32, 33, 34, 34, 35],  // Estimado: punto medio entre P1 y P10
            1  => [14, 17, 19, 28, 29, 30, 30, 31],
        ];

        foreach ($ageGroups as $groupIndex => $group) {
            foreach ($baremo as $percentile => $scores) {
                PercentileTable::updateOrCreate(
                    [
                        'age_min' => $group['min'],
                        'age_max' => $group['max'],
                        'raw_score' => $scores[$groupIndex],
                        'norm_group' => 'montevideo',
                    ],
                    [
                        'percentile' => $percentile,
                        'norm_year' => 2024,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
