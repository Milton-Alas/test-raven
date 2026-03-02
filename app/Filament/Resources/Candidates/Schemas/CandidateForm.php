<?php

namespace App\Filament\Resources\Candidates\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CandidateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos Personales')
                ->description('Información básica del candidato registrado.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre Completo')
                        ->required()
                        //->icon('heroicon-m-user')
                        ->disabled(fn () => !Auth::user()->isAdmin())
                        ->columnSpan(1),

                    TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        //->icon('heroicon-m-envelope')
                        ->disabled()
                        ->columnSpan(1),

                    TextInput::make('dui_nit')
                        ->label('DUI / NIT')
                        ->placeholder('Documento de identidad')
                        ->disabled()
                        ->columnSpan(1),

                    TextInput::make('age')
                        ->label('Edad')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(1),

                    TextInput::make('occupation')
                        ->label('Ocupación / Cargo')
                        ->disabled()
                        ->columnSpan(1),

                    Select::make('education_level')
                        ->label('Nivel Académico')
                        ->options([
                            'primaria' => 'Primaria',
                            'secundaria' => 'Secundaria',
                            'tecnico' => 'Técnico',
                            'universitario' => 'Universitario',
                            'posgrado' => 'Posgrado'
                        ])
                        ->native(false)
                        ->disabled()
                        ->columnSpan(1),
                ])
                ->columns(2),

            Section::make('Estado de la Evaluación')
                ->description('Seguimiento del progreso del test.')
                ->schema([
                    Toggle::make('test_completed')
                        ->label('Evaluación Finalizada')
                        ->onIcon('heroicon-m-check-circle')
                        ->offIcon('heroicon-m-x-circle')
                        ->disabled()
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label('Cuenta de Candidato Activa')
                        ->helperText('Permite o deniega el acceso al sistema de tests.')
                        ->default(true)
                        ->columnSpan(1),

                    DateTimePicker::make('test_started_at')
                        ->label('Fecha de Inicio')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->disabled()
                        ->columnSpan(1),

                    DateTimePicker::make('test_completed_at')
                        ->label('Fecha de Finalización')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->disabled()
                        ->columnSpan(1),
                ])
                ->columns(2)
                // Solo los administradores pueden ver esta sección completa
               ->visible(fn () => Auth::user()->isAdmin()),
            ]);
    }
}
