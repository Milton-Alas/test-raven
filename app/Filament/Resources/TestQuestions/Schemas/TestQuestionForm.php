<?php

namespace App\Filament\Resources\TestQuestions\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use App\Models\TestSeries;

class TestQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la Pregunta (Reactivo)')
                ->description('Configure la ubicación y la imagen principal de la matriz.')
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

                    Toggle::make('is_active')
                        ->label('¿Pregunta Activa?')
                        ->default(true)
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
                ->description('Gestione las alternativas que se presentarán al candidato.')
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
                                ->columnSpan(2),

                            Toggle::make('is_correct')
                                ->label('¿Correcta?')
                                ->onColor('success')
                                ->columnSpan(1),
                        ])
                        ->columns(4)
                        ->minItems(4)
                        ->maxItems(8)
                        ->itemLabel(fn ($state) => isset($state['option_number']) 
                            ? "Opción de Respuesta #{$state['option_number']}" 
                            : 'Nueva Opción')
                        ->collapsible(),
                ]),

            Section::make('Validación de Control')
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
            ]);
    }
}
