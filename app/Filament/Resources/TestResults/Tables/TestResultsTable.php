<?php

namespace App\Filament\Resources\TestResults\Tables;

use App\Models\TestResult;
use App\Services\ActivityLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TestResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.name')
                    ->label('Candidato')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (TestResult $record) => "ID de Sesión: #{$record->test_session_id}"),

                TextColumn::make('total_score')
                    ->label('Puntaje')
                    ->state(fn (TestResult $record): string => "{$record->total_score} / 60")
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('percentile')
                    ->label('Percentil')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 95 => 'success',
                        $state >= 75 => 'info',
                        $state >= 25 => 'warning',
                        default => 'danger',
                    })
                    ->alignCenter(),

                TextColumn::make('diagnostic_range_roman')
                    ->label('Rango')
                    ->badge()
                    // Usamos la relación para determinar el color del rango
                    ->color(fn (TestResult $record): string => match ($record->diagnostic_range) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        4, 5 => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('diagnostic_label')
                    ->label('Clasificación')
                    ->limit(25)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('testSession.started_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                IconColumn::make('is_valid')
                    ->label('Válido')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('diagnostic_range')
                    ->label('Rango Diagnóstico')
                    ->options([
                        1 => 'I - Superior',
                        2 => 'II - Medio Superior',
                        3 => 'III - Término Medio',
                        4 => 'IV - Medio Inferior',
                        5 => 'V - Deficiente',
                    ])
                    ->native(false),

                TernaryFilter::make('is_valid')
                    ->label('Estado de Validez')
                    ->placeholder('Todos')
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (TestResult $record) {
                        if (! class_exists(Pdf::class)) {
                            Notification::make()
                                ->danger()
                                ->title('Paquete PDF no instalado')
                                ->body('Instala barryvdh/laravel-dompdf para habilitar esta acción.')
                                ->send();

                            return null;
                        }

                        $resultado = $record->load(['candidate', 'testSession']);

                        // El candidato puede estar eliminado lógicamente; sin esto
                        // la acción fallaba con un error 500 al generar el nombre.
                        if (! $resultado->candidate) {
                            Notification::make()
                                ->danger()
                                ->title('No se puede generar el informe')
                                ->body('El candidato asociado a este resultado fue eliminado.')
                                ->send();

                            return null;
                        }

                        $candidate = $resultado->candidate;
                        $filename = 'resultado-'.str()->slug($candidate->name).'-'.$resultado->getKey().'.pdf';

                        try {
                            $pdf = Pdf::loadView('reports.raven-result', [
                                'result' => $resultado,
                            ]);

                            // El informe se renderiza aquí para poder auditar la
                            // exportación solo si realmente se generó.
                            $contenido = $pdf->output();
                        } catch (\Throwable $e) {
                            Log::error('Falló la generación del PDF de resultados.', [
                                'test_result_id' => $resultado->getKey(),
                                'candidate_id' => $resultado->candidate_id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->danger()
                                ->title('No se pudo generar el informe')
                                ->body('Ocurrió un error al construir el PDF. Revisa los registros del servidor.')
                                ->send();

                            return null;
                        }

                        // Auditoría: queda registrado quién descargó qué informe.
                        $admin = Auth::user();

                        if ($admin) {
                            app(ActivityLogService::class)->logExport(
                                causer: $admin,
                                subject: $resultado,
                                format: 'pdf',
                                filename: $filename,
                                description: "Informe PDF de resultados descargado por {$admin->name} para el candidato {$candidate->name}.",
                                properties: [
                                    'test_result_id' => $resultado->getKey(),
                                    'test_session_id' => $resultado->test_session_id,
                                    'candidate_id' => $candidate->id,
                                    'candidate_name' => $candidate->name,
                                    'candidate_email' => $candidate->email,
                                    'candidate_dui_nit' => $candidate->dui_nit,
                                    'total_score' => $resultado->total_score,
                                    'percentile' => $resultado->percentile,
                                    'diagnostic_range' => $resultado->diagnostic_range,
                                    'diagnostic_label' => $resultado->diagnostic_label,
                                    'report_scope' => 'diagnostico_completo',
                                ],
                            );
                        }

                        return response()->streamDownload(
                            fn () => print ($contenido),
                            $filename
                        );
                    }),

                EditAction::make()
                    ->visible(fn () => Auth::user()?->isAdmin()),
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
