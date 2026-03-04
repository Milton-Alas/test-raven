<?php

namespace App\Filament\Widgets;

use App\Models\Candidate;
use App\Models\TestSession;
use App\Models\TestResult;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // 1. Candidatos
        $totalCandidates = Candidate::count();
        $activeCandidates = Candidate::where('is_active', true)->count();

        // 2. Tests Hoy
        $testsToday = TestSession::where('status', 'completed')
            ->whereDate('completed_at', Carbon::today())
            ->count();

        // 3. Tests Esta Semana
        $testsThisWeek = TestSession::where('status', 'completed')
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        // 4. Comparativa Mensual
        $thisMonthCount = TestSession::where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        $lastMonthCount = TestSession::where('status', 'completed')
            ->whereMonth('completed_at', now()->subMonth()->month)
            ->whereYear('completed_at', now()->subMonth()->year)
            ->count();

        $trend = $lastMonthCount > 0 
            ? (($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100 
            : ($thisMonthCount > 0 ? 100 : 0);

        // 5. Sesiones Activas
        $inProgress = TestSession::where('status', 'in_progress')->count();

        // 6. Alertas de Validez
        $invalidResults = TestResult::where('is_valid', false)->count();

        return [
            Stat::make('Total Candidatos', $totalCandidates)
                ->description("{$activeCandidates} activos en el sistema")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8])
                ->icon('heroicon-o-users'),

            Stat::make('Tests Hoy', $testsToday)
                ->description($testsToday > 0 ? 'Actividad detectada' : 'Sin ingresos aún')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($testsToday > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-check-badge'),

            Stat::make('Esta Semana', $testsThisWeek)
                ->description('Evaluaciones completadas')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info')
                ->icon('heroicon-o-document-chart-bar'),

            Stat::make('Este Mes', $thisMonthCount)
                ->description(number_format($trend, 1) . '% vs mes anterior')
                ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-presentation-chart-line'),

            Stat::make('En Progreso', $inProgress)
                ->description($inProgress > 0 ? 'Candidatos rindiendo' : 'Pausado')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color($inProgress > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-cpu-chip'),

            Stat::make('Resultados Inválidos', $invalidResults)
                ->description($invalidResults > 0 ? 'Requieren auditoría' : 'Consistencia total')
                ->descriptionIcon($invalidResults > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($invalidResults > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-shield-exclamation'),
        ];
    }
}