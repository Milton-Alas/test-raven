<?php

namespace App\Filament\Widgets;

use App\Models\TestResult;
use App\Models\DiagnosticRange;
use Filament\Widgets\ChartWidget;

class ResultsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'md';

    protected ?string $heading = 'Distribución de Resultados por Rango Diagnóstico';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Obtener rangos diagnósticos ordenados por número (I, II, III, etc.)
        $ranges = DiagnosticRange::orderBy('range_number', 'asc')->get();

        $data = [];
        $labels = [];
        
        // Paleta de colores técnica para psicometría
        $colorPalette = [
            '#10B981', // Verde (Superior)
            '#3B82F6', // Azul (Medio Superior)
            '#F59E0B', // Amarillo (Medio)
            '#F97316', // Naranja (Medio Inferior)
            '#EF4444', // Rojo (Deficiente/Inferior)
        ];

        foreach ($ranges as $index => $range) {
            // Contamos los resultados válidos que caen en este rango específico
            $count = TestResult::where('is_valid', true)
                ->where('diagnostic_range', $range->range_number)
                ->count();

            $labels[] = "Rango {$range->range_roman} ({$range->range_label})";
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Candidatos',
                    'data' => $data,
                    'backgroundColor' => array_slice($colorPalette, 0, count($data)),
                    'borderRadius' => 4, // Bordes redondeados para un look moderno
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}