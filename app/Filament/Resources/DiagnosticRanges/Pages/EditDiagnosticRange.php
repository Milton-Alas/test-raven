<?php

namespace App\Filament\Resources\DiagnosticRanges\Pages;

use App\Filament\Resources\DiagnosticRanges\DiagnosticRangeResource;
use Filament\Resources\Pages\EditRecord;

class EditDiagnosticRange extends EditRecord
{
    protected static string $resource = DiagnosticRangeResource::class;

    /**
     * Sin acciones de borrado: el contenido del test no se elimina porque
     * invalidaría los resultados ya calculados (ver PreservesHistoricalData).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
