<?php

namespace App\Filament\Resources\TestQuestions\Schemas;

use App\Filament\Support\HistoricalContent;
use App\Models\TestSeries;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                HistoricalContent::notice('reactivo'),

                Section::make('Datos de la Pregunta (Reactivo)')
                    ->description('Ubicación y lámina principal de la matriz. En edición se muestran en modo consulta.')
                    ->disabledOn('edit')
                    ->schema([
                        Select::make('test_series_id')
                            ->label('Serie del Test')
                            ->options(TestSeries::where('is_active', true)->orderBy('order')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->columnSpan(1),

                        TextInput::make('question_number')
                            ->label('Número en Serie')
                            ->placeholder('1 - 12')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->columnSpan(1),

                        TextInput::make('global_order')
                            ->label('Orden Global (Secuencia)')
                            ->placeholder('1 - 60')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->columnSpan(1),

                        FileUpload::make('matrix_image_path')
                            ->label('Imagen de la Matriz Principal')
                            ->image()
                            ->directory('test-images/matrices')
                            ->imageEditor()
                            ->required()
                            ->imagePreviewHeight('200')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Opciones de Respuesta')
                    ->description('Alternativas que se presentan al candidato. Forman parte de la lámina del reactivo, por eso no se reescriben al editar.')
                    ->disabledOn('edit')
                    ->schema([
                        Repeater::make('answerOptions')
                            ->relationship('answerOptions')
                            ->schema([
                                TextInput::make('option_number')
                                    ->label('Nº')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(8)
                                    ->required()
                                    ->columnSpan(1),

                                FileUpload::make('option_image_path')
                                    ->label('Imagen Opción')
                                    ->image()
                                    ->directory('test-images/options')
                                    ->imagePreviewHeight('80')
                                    ->required()
                                    ->columnSpan(3),
                            ])
                            ->columns(4)
                            ->minItems(4)
                            ->maxItems(8)
                            ->itemLabel(fn ($state) => isset($state['option_number'])
                                ? "Opción de Respuesta #{$state['option_number']}"
                                : 'Nueva Opción')
                            ->collapsible(),
                    ]),

                Section::make('Clave de Corrección')
                    ->description('Número de la opción correcta. Determina el puntaje, así que no se puede cambiar: hacerlo alteraría el resultado de tests ya rendidos.')
                    ->disabledOn('edit')
                    ->schema([
                        TextInput::make('correct_answer')
                            ->label('Número de Opción Correcta (Referencia)')
                            ->helperText('Indique el número (1-8) que coincide con la clave de respuesta.')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(8),
                    ])
                    ->columns(1),

                Section::make('Vigencia')
                    ->description('Desactivar un reactivo lo retira de tests nuevos sin borrar la historia.')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('¿Pregunta Activa?')
                            ->helperText('Los reactivos inactivos no se incluyen en tests nuevos.')
                            ->default(true)
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
