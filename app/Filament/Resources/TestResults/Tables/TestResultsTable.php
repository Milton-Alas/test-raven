<?php

namespace App\Filament\Resources\TestResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TestResult;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Barryvdh\DomPDF\Facade\Pdf;

class TestResultsTable
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
                ->description(fn (TestResult $record) => "ID de Sesión: #{$record->test_session_id}"),

                TextColumn::make('total_score')
                    ->label('Puntaje')
                    ->state(fn (TestResult $record): string => "{$record->total_score} / 60")
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('percentile')
                    ->label('Percentil')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 95 => 'success',
                        $state >= 75 => 'info',
                        $state >= 25 => 'warning',
                        default => 'danger',
                    })
                    ->alignCenter(),

                TextColumn::make('diagnostic_range_roman')
                    ->label('Rango')
                    ->badge()
                    // Usamos la relación para determinar el color del rango
                    ->color(fn (TestResult $record): string => match ($record->diagnostic_range) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        4, 5 => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('diagnostic_label')
                    ->label('Clasificación')
                    ->limit(25)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('testSession.started_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                IconColumn::make('is_valid')
                    ->label('Válido')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                    SelectFilter::make('diagnostic_range')
                    ->label('Rango Diagnóstico')
                    ->options([
                        1 => 'I - Superior',
                        2 => 'II - Medio Superior',
                        3 => 'III - Término Medio',
                        4 => 'IV - Medio Inferior',
                        5 => 'V - Deficiente',
                    ])
                    ->native(false),

                TernaryFilter::make('is_valid')
                    ->label('Estado de Validez')
                    ->placeholder('Todos')
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (TestResult $record) {
                        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                            Notification::make()
                                ->danger()
                                ->title('Paquete PDF no instalado')
                                ->body('Instala barryvdh/laravel-dompdf para habilitar esta acción.')
                                ->send();

                            return null;
                        }

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.raven-result', [
                            'result' => $record->load(['candidate', 'testSession']),
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'resultado-'.str()->slug($record->candidate->name).'-'.$record->id.'.pdf'
                        );
                    }),

                EditAction::make()
                    ->visible(fn () => Auth::user()?->isAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
