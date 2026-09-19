<?php

namespace App\Filament\Resources\TestSeries\Schemas;

use App\Filament\Support\HistoricalContent;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestSeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                HistoricalContent::notice('serie'),

                Section::make('Datos de la Serie')
                    ->description('Identifican la serie y determinan cómo se agrupan y puntúan los reactivos. En edición se muestran en modo consulta.')
                    // El contenido de la sección se deshabilita completo al editar:
                    // reescribirlo cambiaría el puntaje de tests ya rendidos. La
                    // vigencia vive aparte, en su propia sección.
                    ->disabledOn('edit')
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
                    ])
                    ->columns(2),

                Section::make('Vigencia')
                    ->description('Retirar una serie no borra nada: deja de ofrecerse en tests nuevos y la historia se conserva.')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('¿La serie está activa?')
                            ->helperText('Las series inactivas no estarán disponibles para nuevos tests.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
