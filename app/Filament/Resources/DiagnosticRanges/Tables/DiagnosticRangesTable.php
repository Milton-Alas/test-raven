<?php

namespace App\Filament\Resources\DiagnosticRanges\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\DiagnosticRange;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;

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
                    ->state(fn (DiagnosticRange $record): string => 
                        "{$record->percentile_min}% - {$record->percentile_max}%"
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
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
