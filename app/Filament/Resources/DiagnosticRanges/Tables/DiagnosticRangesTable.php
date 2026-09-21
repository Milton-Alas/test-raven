<?php

namespace App\Filament\Resources\DiagnosticRanges\Tables;

use App\Models\DiagnosticRange;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiagnosticRangesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('range_number')
                    ->label('Rango')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        4, 5 => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('percentile_range')
                    ->label('Percentiles')
                    ->state(fn (DiagnosticRange $record): string => "{$record->percentile_min}% - {$record->percentile_max}%"
                    )
                    ->icon('heroicon-m-variable')
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('range_label')
                    ->label('Etiqueta')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('diagnostic_label')
                    ->label('Diagnóstico')
                    ->limit(40)
                    ->searchable()
                    ->description(fn (DiagnosticRange $record) => str($record->interpretation)->limit(50)),

                TextColumn::make('updated_at')
                    ->label('Última Modificación')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('range_number', 'asc')
            ->filters([
                //
            ])
            ->recordActions([
                // Solo consulta: los rangos son la clave de calificación y no se
                // modifican desde el panel (ver DiagnosticRangeResource).
                ViewAction::make(),
            ])
            ->toolbarActions([
                // Sin acciones destructivas: el modelo las prohíbe.
            ]);
    }
}
