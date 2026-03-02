<?php

namespace App\Filament\Resources\PercentileTables\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PercentileTable;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;


class PercentileTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('age_range')
                ->label('Rango de Edad')
                ->state(fn (PercentileTable $record): string => "{$record->age_min} - {$record->age_max} años")
                ->icon('heroicon-m-identification')
                ->color('gray')
                ->sortable(['age_min']),

                TextColumn::make('raw_score')
                    ->label('Puntaje Bruto')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('percentile')
                    ->label('Percentil')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'info',
                        $state >= 25 => 'warning',
                        default => 'danger',
                    })
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('norm_group')
                    ->label('Grupo Normativo')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->description(fn (PercentileTable $record) => "Año: {$record->norm_year}"),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('age_min', 'asc')
            ->defaultPaginationPageOption(50)
            ->filters([
                TernaryFilter::make('is_active')
                ->label('Estado de Baremo')
                ->placeholder('Todos')
                ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
