<?php

namespace App\Filament\Resources\PercentileTables\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class PercentileTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rango de Edad Aplicable')
                ->description('Defina el segmento de edad para este baremo.')
                ->schema([
                    TextInput::make('age_min')
                        ->label('Edad Mínima')
                        ->required()
                        ->numeric()
                        ->minValue(12)
                        ->maxValue(100)
                        ->suffix('años')
                        ->columnSpan(1),

                    TextInput::make('age_max')
                        ->label('Edad Máxima')
                        ->required()
                        ->numeric()
                        ->minValue(12)
                        ->maxValue(100)
                        ->gt('age_min') // Validación: máximo mayor al mínimo
                        ->suffix('años')
                        ->columnSpan(1),
                ])
                ->columns(2),

            Section::make('Conversión de Puntajes')
                ->description('Relación entre el puntaje bruto obtenido y el percentil resultante.')
                ->schema([
                    TextInput::make('raw_score')
                        ->label('Puntaje Bruto (PB)')
                        ->placeholder('0 - 60')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(60)
                        ->columnSpan(1),

                    TextInput::make('percentile')
                        ->label('Percentil (PC)')
                        ->placeholder('1 - 99')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->suffix('%')
                        ->columnSpan(1),
                ])
                ->columns(2),

            Section::make('Grupo Normativo y Estado')
                ->description('Referencia estadística y vigencia del baremo.')
                ->schema([
                    TextInput::make('norm_group')
                        ->label('Grupo de Norma')
                        ->default('Montevideo')
                        ->required()
                        ->maxLength(50)
                        ->columnSpan(1),

                    TextInput::make('norm_year')
                        ->label('Año de Baremo')
                        ->placeholder('Ej: 2024')
                        ->numeric()
                        ->minValue(1990)
                        ->maxValue(2030)
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label('¿Baremo Activo?')
                        ->helperText('Solo los baremos activos se usarán para calcular resultados.')
                        ->default(true)
                        ->columnSpan(1),
                ])
                ->columns(3),
            ]);
    }
}
