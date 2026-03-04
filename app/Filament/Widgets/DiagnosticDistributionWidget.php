<?php

namespace App\Filament\Widgets;

use App\Models\TestResult;
use App\Models\DiagnosticRange;
use Filament\Widgets\ChartWidget;

class DiagnosticDistributionWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    // usar 'full', 'md' o 'sm' según cómo quieras que se vea en el grid
    protected int|string|array $columnSpan = 'md'; 

    protected ?string $heading = 'Distribución por Diagnóstico (%)';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $ranges = DiagnosticRange::orderBy('range_number')->get();
        // Usamos where('is_valid', true) para asegurar compatibilidad directa
        $totalResults = TestResult::where('is_valid', true)->count();

        $data = [];
        $labels = [];
        
        $colorPalette = [
            '#10B981', // Verde - Superior
            '#3B82F6', // Azul - Medio Superior
            '#F59E0B', // Amarillo - Medio
            '#F97316', // Naranja - Medio Inferior
            '#EF4444', // Rojo - Inferior
        ];

        foreach ($ranges as $index => $range) {
            $count = TestResult::where('is_valid', true)
                ->where('diagnostic_range', $range->range_number)
                ->count();

            $percentage = $totalResults > 0
                ? round(($count / $totalResults) * 100, 1)
                : 0;

            // El label ahora incluye el porcentaje directamente para mayor claridad
            $labels[] = "{$range->range_roman} - {$range->range_label} ({$percentage}%)";
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colorPalette, 0, count($data)),
                    'hoverOffset' => 15, // Efecto visual al pasar el ratón
                    'borderColor' => '#FFFFFF',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right', // Mejor visibilidad en pantallas anchas
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 20,
                    ],
                ],
            ],
            'cutout' => '65%', // Hace el centro del "donas" un poco más grande y moderno
            'radius' => '90%',
        ];
    }
}