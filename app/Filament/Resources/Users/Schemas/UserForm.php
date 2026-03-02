<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Models\User;
use Filament\Forms\Components\Toggle;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
        Section::make('Datos del Usuario')
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpan(1),

                Select::make('role')
                    ->label('Rol')
                    ->options(User::getRoles())
                    ->required()
                    ->default('reporter')
                    ->native(false) // Estilo más limpio
                    ->columnSpan(1),

                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpan(1),
            ])
            ->columns(2),

        Section::make('Seguridad')
            ->schema([
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->same('passwordConfirmation')
                    ->revealable()
                    ->columnSpan(1),

                TextInput::make('passwordConfirmation')
                    ->label('Confirmar Contraseña')
                    ->password()
                    ->dehydrated(false)
                    ->revealable()
                    ->columnSpan(1),
            ])
            ->columns(2),
    ]);
    
}
}