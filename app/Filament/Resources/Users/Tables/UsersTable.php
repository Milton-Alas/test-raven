<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;



class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable()
                ->weight('bold')
                
                ->icon(fn (User $record): string => match ($record->role) {
                    'admin' => 'heroicon-o-shield-check',
                    'reporter' => 'heroicon-o-document-chart-bar',
                    default => 'heroicon-o-user',
                })
                ->iconColor(fn (User $record): string => match ($record->role) {
                    'admin' => 'danger',
                    'reporter' => 'info',
                    default => 'gray',
                }),

                TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->copyable()
                    ->placeholder('Sin email'),

                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn ($state) => User::getRoles()[$state] ?? $state)
                    ->color(fn (User $record): string => match ($record->role) {
                        'admin' => 'danger',
                        'reporter' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('activity_status')
                    ->label('Estado de Actividad')
                    ->state(function (User $record): string {
                        if (!$record->last_login_at) return 'Inactivo';
                        return $record->last_login_at->diffInDays(now()) > 30 ? 'Ausente' : 'Activo';
                    })
                    ->badge()
                    ->color(function (User $record): string {
                        if (!$record->last_login_at) return 'gray';
                        $days = $record->last_login_at->diffInDays(now());
                        if ($days > 30) return 'warning';
                        return 'success';
                    }),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                ->label('Filtrar por Rol')
                ->options(User::getRoles())
                ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),

            ])
            ->actions([
            
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    // Evita que el usuario se borre a sí mismo
                    ->disabled(fn ($record) => $record->id === Auth::id()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
