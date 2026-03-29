<?php

namespace App\Filament\Resources\Candidates\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;


class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable()
                ->weight('bold'),

                TextColumn::make('dui_nit')
                    ->label('DUI/NIT')
                    ->searchable()
                    ->sortable(),

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

                TextColumn::make('latestTestResult.diagnostic_label')
                    ->label('Diagnóstico')
                    ->weight('bold')
                    ->placeholder('-'),

                TextColumn::make('latestTestResult.total_score')
                    ->label('Puntaje Total')
                    ->placeholder('-')
                    ->sortable()
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
                    BulkAction::make('export')
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (EloquentCollection $records): StreamedResponse {
                            return static::exportToCsv($records);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function exportToCsv(EloquentCollection $records): StreamedResponse
    {
        $records->loadMissing('latestTestResult');

        $filename = 'candidatos_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'ID',
            'Nombre',
            'Correo',
            'DUI/NIT',
            'Edad',
            'Activo',
            'Test completado',
            'Percentil',
            'Diagnostico',
            'Puntaje total',
            'Fecha finalizacion test',
        ];

        return response()->streamDownload(function () use ($records, $headers): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // BOM UTF-8 para compatibilidad con Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);

            foreach ($records as $candidate) {
                $result = $candidate->latestTestResult;

                fputcsv($handle, [
                    $candidate->id,
                    $candidate->name,
                    $candidate->email,
                    $candidate->dui_nit,
                    $candidate->age,
                    $candidate->is_active ? 'Si' : 'No',
                    $candidate->test_completed ? 'Si' : 'No',
                    $result?->percentile,
                    $result?->diagnostic_label,
                    $result?->total_score,
                    $candidate->test_completed_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
