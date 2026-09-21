<?php

namespace App\Filament\Resources\TestQuestions\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                    ->square()
                    ->size(50)
                    ->extraImgAttributes(['class' => 'rounded shadow-sm']),*/
                ImageColumn::make('matrix_image_path')
                    ->label('Matriz')
                    ->disk('public')
                    // La ruta guardada en la base ya es la definitiva
                    // (matrices/{serie}/{archivo}); antes se le anteponía
                    // "test-images/", un prefijo que la base nunca escribe y que
                    // hacía que la miniatura del panel se viera solo porque en la
                    // máquina de desarrollo existía una copia duplicada de los
                    // archivos. Unificado a la convención de la base, que es la
                    // misma que resuelve la vista del candidato.
                    ->state(fn ($record) => $record->matrix_image_path)
                    ->square()
                    ->size(60)
                    ->extraImgAttributes(['class' => 'rounded shadow-sm border border-gray-200']),
                /*TextColumn::make('options_count')
                    ->label('Opciones')
                    //->counts('options') // Asumiendo que la relación es 'options'
                    ->badge()
                    ->color(fn ($state): string => $state < 4 ? 'danger' : 'gray')
                    ->alignCenter(),*/
                /*  TextColumn::make('answer_options_count')
                    ->label('Opciones')
                    ->counts('answerOptions')
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
                // Sin acciones de registro: el banco de reactivos es de solo
                // lectura en el panel (ver TestQuestionResource). El borrado,
                // además, está prohibido en el modelo porque arrastraría en
                // cascada las respuestas de los candidatos.
            ])
            ->toolbarActions([
                // Sin acciones masivas: no hay nada destructivo que ofrecer.
            ]);
    }
}
