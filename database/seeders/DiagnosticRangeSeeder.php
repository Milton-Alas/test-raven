<?php

namespace Database\Seeders;

use App\Models\DiagnosticRange;
use Illuminate\Database\Seeder;

class DiagnosticRangeSeeder extends Seeder
{
    public function run(): void
    {
        // Tabla de Diagnóstico de Capacidad Intelectual - Manual Raven SPM
        // 9 sub-rangos oficiales con percentiles exactos
        // range_number = rango base (1-5), range_label = sub-rango (I, II+, II, etc.)

        $ranges = [
            [
                'percentile_min' => 95,
                'percentile_max' => 100,
                'range_number' => 1,
                'range_label' => 'I',
                'diagnostic_label' => 'Intelectualmente Superior',
                'interpretation' => 'Rango I - Superior: Capacidad intelectual superior al 95% de la población de su edad. Indica una capacidad de observación y razonamiento analógico sobresaliente.',
            ],
            [
                'percentile_min' => 90,
                'percentile_max' => 94,
                'range_number' => 2,
                'range_label' => 'II+',
                'diagnostic_label' => 'Superior al Término Medio',
                'interpretation' => 'Rango II+ - Superior al Término Medio: Capacidad intelectual claramente superior al promedio. Se encuentra por encima del percentil 90 de su grupo normativo.',
            ],
            [
                'percentile_min' => 75,
                'percentile_max' => 89,
                'range_number' => 2,
                'range_label' => 'II',
                'diagnostic_label' => 'Superior al Término Medio',
                'interpretation' => 'Rango II - Superior al Término Medio: Capacidad intelectual definitivamente superior al promedio. Demuestra buena capacidad para el razonamiento no verbal.',
            ],
            [
                'percentile_min' => 51,
                'percentile_max' => 74,
                'range_number' => 3,
                'range_label' => 'III+',
                'diagnostic_label' => 'Término Medio',
                'interpretation' => 'Rango III+ - Término Medio (superior): Capacidad intelectual dentro del promedio, tendiendo al rango superior. Razonamiento analógico adecuado.',
            ],
            [
                'percentile_min' => 50,
                'percentile_max' => 50,
                'range_number' => 3,
                'range_label' => 'III',
                'diagnostic_label' => 'Término Medio',
                'interpretation' => 'Rango III - Término Medio: Capacidad intelectual exactamente en el promedio esperado para su edad.',
            ],
            [
                'percentile_min' => 26,
                'percentile_max' => 49,
                'range_number' => 3,
                'range_label' => 'III-',
                'diagnostic_label' => 'Término Medio',
                'interpretation' => 'Rango III- - Término Medio (inferior): Capacidad intelectual dentro del promedio, tendiendo al rango inferior.',
            ],
            [
                'percentile_min' => 11,
                'percentile_max' => 25,
                'range_number' => 4,
                'range_label' => 'IV+',
                'diagnostic_label' => 'Inferior al Término Medio',
                'interpretation' => 'Rango IV+ - Inferior al Término Medio: Capacidad intelectual por debajo del promedio. Puede presentar dificultades en tareas de razonamiento abstracto.',
            ],
            [
                'percentile_min' => 6,
                'percentile_max' => 10,
                'range_number' => 4,
                'range_label' => 'IV',
                'diagnostic_label' => 'Inferior al Término Medio',
                'interpretation' => 'Rango IV - Inferior al Término Medio: Capacidad intelectual definitivamente por debajo del promedio. Se recomienda evaluación complementaria.',
            ],
            [
                'percentile_min' => 0,
                'percentile_max' => 5,
                'range_number' => 5,
                'range_label' => 'V',
                'diagnostic_label' => 'Deficiente',
                'interpretation' => 'Rango V - Deficiente: Capacidad intelectual significativamente por debajo del promedio. Se recomienda evaluación detallada con pruebas complementarias.',
            ],
        ];

        // Limpiar y reinsertar para actualizar de 5 a 9 rangos
        DiagnosticRange::truncate();

        foreach ($ranges as $range) {
            DiagnosticRange::create($range);
        }
    }
}
