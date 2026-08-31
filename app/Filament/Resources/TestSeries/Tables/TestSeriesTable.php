<?php

namespace App\Filament\Resources\TestSeries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TestSeries;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;

class TestSeriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('Nº')
                    ->sortable()
                    ->width('50px')
                    ->alignCenter()
                    ->color('gray'),

                TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->width('80px'),

                TextColumn::make('name')
                    ->label('Nombre de la Serie')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->description ? str($record->description)->limit(40) : null),

                TextColumn::make('questions_count')
                    ->label('Total Preguntas')
                    ->counts('questions')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                ->label('Estado de Serie')
                ->placeholder('Todas')
                ->trueLabel('Solo Activas')
                ->falseLabel('Solo Inactivas')
                ->native(false),
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
