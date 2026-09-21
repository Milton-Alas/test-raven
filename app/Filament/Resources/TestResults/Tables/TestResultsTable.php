<?php

namespace App\Filament\Resources\TestResults\Tables;

use App\Filament\Resources\TestResults\TestResultResource;
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
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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

            ->headerActions([
                ExportAction::make('exportar_excel')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    // Fuera del alcance del rol `evaluador`: consulta y PDF
                    // individual, sin exportación masiva (ver
                    // TestResultResource::canExport()).
                    ->visible(fn (): bool => TestResultResource::canExport())
                    ->exports([
                        ExcelExport::make('excel')
                            ->fromTable()
                            ->withFilename('resultados-'.now()->format('Y-m-d')),
                    ])
                    ->after(function (ExportAction $action): void {
                        self::registrarExportacionMasiva(
                            $action,
                            'xlsx'
                        );
                    }),

                ExportAction::make('exportar_csv')
                    ->label('Exportar a CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn (): bool => TestResultResource::canExport())
                    ->exports([
                        ExcelExport::make('csv')
                            ->fromTable()
                            ->withWriterType(Excel::CSV)
                            ->withFilename('resultados-'.now()->format('Y-m-d')),
                    ])
                    ->after(function (ExportAction $action): void {
                        self::registrarExportacionMasiva(
                            $action,
                            'csv'
                        );
                    }),
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

                        // El informe incluye el logo institucional en PNG y DomPDF lo
                        // incrusta con la extensión GD. Sin ella, el error nativo
                        // ("The PHP GD extension is required") no dice qué hacer, así
                        // que se comprueba antes y se explica la solución.
                        if (! extension_loaded('gd')) {
                            Log::error('No se puede generar el PDF: falta la extensión GD de PHP.', [
                                'test_result_id' => $record->getKey(),
                            ]);

                            Notification::make()
                                ->danger()
                                ->title('Falta la extensión GD de PHP')
                                ->body(
                                    'El informe incluye el logo en PNG y DomPDF necesita la extensión GD para incrustarlo. '
                                    .'Instálala con: sudo apt-get install -y php8.4-gd && sudo systemctl restart php8.4-fpm '
                                    .'(si usas el servidor embebido, basta reiniciar "php artisan serve").'
                                )
                                ->persistent()
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

                            // Si la causa es una extensión faltante, se dice cuál: el
                            // mensaje genérico obliga a mirar el log por algo que el
                            // usuario del panel puede resolver o reportar.
                            $causa = str_contains($e->getMessage(), 'GD extension')
                                ? 'Falta la extensión GD de PHP, necesaria para incrustar el logo del informe. '
                                    .'Instálala con: sudo apt-get install -y php8.4-gd'
                                : 'Ocurrió un error al construir el PDF. Revisa los registros del servidor.';

                            Notification::make()
                                ->danger()
                                ->title('No se pudo generar el informe')
                                ->body($causa)
                                ->persistent()
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
                    // Visibilidad explícita: Filament no liga las acciones
                    // masivas a los métodos canXAny() del Resource en este contexto.
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => TestResultResource::canDeleteAny()),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (): bool => TestResultResource::canForceDeleteAny()),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => TestResultResource::canRestoreAny()),
                ]),
            ]);
    }

    private static function registrarExportacionMasiva(
        ExportAction $action,
        string $format
    ): void {
        $admin = Auth::user();

        if (! $admin) {
            return;
        }

        $filename = 'resultados-'.now()->format('Y-m-d').'.'.$format;
        $downloadedAt = now()->toDateTimeString();

        $resultado = $action
            ->getLivewire()
            ->getFilteredTableQuery()
            ->first();

        if (! $resultado) {
            return;
        }

        app(ActivityLogService::class)->logExport(
            causer: $admin,
            subject: $resultado,
            format: $format,
            filename: $filename,
            description: "Exportación {$format} descargada por {$admin->name}.",
            properties: [
                'filename' => $filename,
                'downloaded_by' => $admin->name,
                'downloaded_at' => $downloadedAt,
            ],
        );
    }
}
