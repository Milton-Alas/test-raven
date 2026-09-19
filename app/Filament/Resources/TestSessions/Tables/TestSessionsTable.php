<?php

namespace App\Filament\Resources\TestSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TestSession;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\View\View;

class TestSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.name')
                ->label('Candidato')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->description(fn (TestSession $record) => $record->ip_address ?? 'Sin IP registrada'),

                TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'in_progress' => 'warning',
                        'completed'   => 'success',
                        'timeout'     => 'danger',
                        default       => 'gray'
                    })
                    ->icon(fn ($state) => match($state) {
                        'in_progress' => 'heroicon-m-play',
                        'completed'   => 'heroicon-m-check-badge',
                        'timeout'     => 'heroicon-m-clock',
                        default       => 'heroicon-m-question-mark-circle'
                    }),

                TextColumn::make('progress_percentage')
                    ->label('Progreso')
                    ->state(fn (TestSession $record) => round($record->progress_percentage) . '%')
                    ->alignCenter()
                    ->color(fn ($state) => match(true) {
                        (int)$state >= 100 => 'success',
                        (int)$state >= 50  => 'info',
                        default            => 'warning'
                    })
                    ->weight('bold'),

                IconColumn::make('test_result_exists')
                    ->label('¿Hay Resultado?')
                    ->exists('testResult')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                ->label('Filtrar por Estado')
                ->options([
                    'in_progress' => 'En Progreso',
                    'completed'   => 'Completado',
                    'timeout'     => 'Tiempo Expirado',
                ])
                ->native(false),
            ])
            ->recordActions([
                //ViewAction::make(),
                ViewAction::make()
                    ->label('Ver Detalles')
                    ->icon('heroicon-m-eye')
                    ->color('primary'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorize(fn () => \Illuminate\Support\Facades\Gate::allows('deleteAny', TestSession::class)),
                    ForceDeleteBulkAction::make()->authorize(fn () => \Illuminate\Support\Facades\Gate::allows('forceDeleteAny', TestSession::class)),
                    RestoreBulkAction::make()->authorize(fn () => \Illuminate\Support\Facades\Gate::allows('restoreAny', TestSession::class)),
                ]),
            ]);
    }
}
