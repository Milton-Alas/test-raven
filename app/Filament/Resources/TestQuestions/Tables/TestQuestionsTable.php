<?php

namespace App\Filament\Resources\TestQuestions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\DeleteAction;

class TestQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('global_order')
                ->label('№')
                ->sortable()
                ->weight('bold')
                ->alignCenter(),

                TextColumn::make('series.code')
                    ->label('Serie')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('question_number')
                    ->label('Ítem')
                    ->description(fn ($record) => "Serie {$record->series?->code}")
                    ->alignCenter(),

                /*ImageColumn::make('matrix_image_path')
                    ->label('Matriz')
                    ->square() // Las matrices de Raven suelen ser cuadradas, se ven mejor así que circulares
                    ->size(50)
                    ->extraImgAttributes(['class' => 'rounded shadow-sm']),*/
                    ImageColumn::make('matrix_image_path')
                    ->label('Matriz')
                    ->disk('public')
                    ->state(fn ($record) => $record->matrix_image_path ? "test-images/{$record->matrix_image_path}" : null)
                    ->square()
                    ->size(60)
                    ->extraImgAttributes(['class' => 'rounded shadow-sm border border-gray-200']),   
                /*TextColumn::make('options_count')
                    ->label('Opciones')
                    //->counts('options') // Asumiendo que la relación es 'options'
                    ->badge()
                    ->color(fn ($state): string => $state < 4 ? 'danger' : 'gray')
                    ->alignCenter(),*/
                  /*  TextColumn::make('answer_options_count') // El nombre automático de Filament para counts('answerOptions')
                    ->label('Opciones')
                    ->counts('answerOptions') // Nombre exacto de la relación en tu modelo TestQuestion
                    ->badge()
                    ->color(fn ($state, $record): string => 
                        // Lógica inteligente: Series A,B deben tener 6. C,D,E deben tener 8.
                        in_array($record->series->code, ['A', 'B']) 
                            ? ($state === 6 ? 'success' : 'danger')
                            : ($state === 8 ? 'success' : 'danger')
                    )
                    ->description(fn ($record): string => 
                        in_array($record->series->code, ['A', 'B']) ? 'Req: 6' : 'Req: 8'
                    )
                    ->alignCenter(),*/

                TextColumn::make('correct_answer')
                    ->label('Clave')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('global_order', 'asc')
            ->filters([
                SelectFilter::make('test_series_id')
                ->label('Filtrar por Serie')
                ->relationship('series', 'name')
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
