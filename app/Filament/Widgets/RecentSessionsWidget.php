<?php

namespace App\Filament\Widgets;

use App\Models\TestSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class RecentSessionsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    // usar 'full', 'md' o 'sm' según cómo quieras que se vea en el grid
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Últimas Sesiones de Test';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TestSession::query()
                    ->with(['candidate', 'testResult'])
                    ->latest('started_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('candidate.name')
                    ->label('Candidato')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_progress' => 'En Progreso',
                        'completed'   => 'Completado',
                        'timeout'     => 'Expirado',
                        default       => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in_progress' => 'warning',
                        'completed'   => 'success',
                        'timeout'     => 'danger',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progreso')
                    ->alignCenter()
                    ->formatStateUsing(function ($record) {
                        $percentage = round($record->progress_percentage);
                        // Definición de colores para Tailwind
                        $colorClass = match (true) {
                            $percentage >= 90 => 'bg-emerald-500',
                            $percentage >= 50 => 'bg-amber-500',
                            default           => 'bg-rose-500',
                        };
                        
                        return new HtmlString("
                            <div class='flex items-center justify-center gap-3 w-full min-w-[120px]'>
                                <div class='flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden'>
                                    <div class='h-full {$colorClass} transition-all duration-500' style='width: {$percentage}%'></div>
                                </div>
                                <span class='text-xs font-medium text-gray-600 dark:text-gray-400'>{$percentage}%</span>
                            </div>
                        ");
                    }),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado hace')
                    ->since() // Muestra "hace 5 minutos", muy útil para monitoreo
                    ->dateTimeTooltip()
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_result')
                    ->label('Resultado')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->testResult()->exists())
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('testResult.percentile')
                    ->label('PC')
                    ->placeholder('-')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->actions([
              /*  Action::make('view')
                    ->label('Monitorear')
                    ->icon('heroicon-m-magnifying-glass')
                    ->url(fn ($record) => route('filament.admin.resources.test-sessions.view', $record))
                    ->color('gray'),*/
            ])
            ->paginated(false);
    }
}