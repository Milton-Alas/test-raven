<?php

namespace App\Filament\Resources\TestSeries\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;


class TestSeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la Serie')
                ->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->placeholder('Ej: A, B, C...')
                        ->required()
                        ->maxLength(1)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->columnSpan(1),

                    TextInput::make('order')
                        ->label('Orden de Visualización')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('name')
                        ->label('Nombre de la Serie')
                        ->placeholder('Ingrese el nombre descriptivo')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(), 

                    Textarea::make('description')
                        ->label('Descripción')
                        ->placeholder('Opcional: Detalles adicionales sobre esta serie...')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('¿La serie está activa?')
                        ->helperText('Las series inactivas no estarán disponibles para nuevos tests.')
                        ->default(true)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            ]);
    }
}
