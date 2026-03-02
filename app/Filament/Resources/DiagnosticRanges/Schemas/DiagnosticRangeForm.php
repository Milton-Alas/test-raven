<?php

namespace App\Filament\Resources\DiagnosticRanges\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class DiagnosticRangeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rango de Percentiles')
                ->description('Defina los límites numéricos para este diagnóstico.')
                ->schema([
                    TextInput::make('range_number')
                        ->label('Nivel/Número')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('percentile_min')
                        ->label('Percentil Mínimo')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('%')
                        ->columnSpan(1),

                    TextInput::make('percentile_max')
                        ->label('Percentil Máximo')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('%')
                        ->gt('percentile_min') // Validación: debe ser mayor que el mínimo
                        ->columnSpan(1),
                ])
                ->columns(3),

            Section::make('Etiquetas y Resultados')
                ->description('Información textual que se mostrará en los reportes.')
                ->schema([
                    TextInput::make('range_label')
                        ->label('Etiqueta del Rango')
                        ->placeholder('Ej: Rango Medio')
                        ->required()
                        ->maxLength(50)
                        ->columnSpan(1),

                    TextInput::make('diagnostic_label')
                        ->label('Diagnóstico')
                        ->placeholder('Ej: Promedio Normal')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(1),

                    Textarea::make('interpretation')
                        ->label('Interpretación Clínica')
                        ->placeholder('Escriba aquí la descripción detallada para el reporte...')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            ]);
    }
}
