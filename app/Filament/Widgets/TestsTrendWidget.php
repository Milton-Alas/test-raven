<?php

namespace App\Filament\Widgets;

use App\Models\TestSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TestsTrendWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    // usar 'full', 'md' o 'sm' según cómo quieras que se vea en el grid
    protected int|string|array $columnSpan = 'md';

    protected ?string $heading = 'Evolución de Tests (Últimos 30 días)';

    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Optimizamos la consulta para obtener los conteos agrupados por día en una sola ejecución
        $results = TestSession::select(DB::raw('DATE(completed_at) as date'), DB::raw('count(*) as aggregate'))
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('aggregate', 'date');

        // Generamos los 30 días para asegurar que los días sin tests muestren 0
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d/m');
            $data[] = $results->get($date, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tests Completados',
                    'data' => $data,
                    'borderColor' => '#1F4E79',
                    'backgroundColor' => 'rgba(31, 78, 121, 0.1)',
                    'fill' => true,
                    'tension' => 0.4, // Curva suavizada y moderna
                    'pointRadius' => 3,
                    'pointHitRadius' => 10,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}