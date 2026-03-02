<?php

namespace App\Filament\Resources\TestResults\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class TestResultForm
{

        public static function configure(Schema $schema): Schema
        {
            return $schema
                ->components([
                    Section::make('Validación del Reporte')
                        ->description('Ajustes manuales sobre la validez del resultado.')
                        ->schema([
                            Toggle::make('is_valid')
                                ->label('Test Válido')
                                ->helperText('Desactive si el comportamiento del candidato fue sospechoso.')
                                ->onColor('success')
                                ->offColor('danger')
                                ->default(true),
    
                            Textarea::make('validity_notes')
                                ->label('Notas de Validez')
                                ->placeholder('Razón por la cual se invalida o se observa el test...')
                                ->rows(3)
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ])
                        ->visible(fn () => Auth::user()->isAdmin())
                        ->columns(1),
                ]);
        }
/*
public static function configure(Schema $schema): Schema
{
    return $form->schema([
        Section::make('Validación del Reporte')
            ->description('Ajustes manuales sobre la validez del resultado.')
                ->schema([
                    Toggle::make('is_valid')
                        ->label('Test Válido')
                        ->helperText('Desactive si el comportamiento del candidato fue sospechoso.')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true),

                    Textarea::make('validity_notes')
                        ->label('Notas de Validez')
                        ->placeholder('Razón por la cual se invalida o se observa el test...')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->visible(fn () => Auth::user()->isAdmin())
                ->columns(1),
        ]);
    }*/
}
