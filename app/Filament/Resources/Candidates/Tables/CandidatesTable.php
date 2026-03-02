<?php

namespace App\Filament\Resources\Candidates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Candidate;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TernaryFilter;
Use App\Models\User;


class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Nombre del Candidato')
                ->searchable()
                ->sortable()
                ->weight('bold'),

                TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                TextColumn::make('age')
                    ->label('Edad')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('test_completed')
                    ->label('Estado Test')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->alignCenter(),

                TextColumn::make('latestTestResult.percentile')
                    ->label('Percentil')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'info',
                        $state >= 25 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                TrashedFilter::make(),
                TernaryFilter::make('test_completed')
                ->label('¿Test Finalizado?')
                ->placeholder('Todos')
                ->native(false),
                TernaryFilter::make('is_active')
                    ->label('Estado de Cuenta')
                    ->placeholder('Todos')
                    ->native(false),
                ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => Auth::user()->isAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
