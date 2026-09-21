<?php

namespace App\Filament\Resources\DiagnosticRanges\Pages;

use App\Filament\Resources\DiagnosticRanges\DiagnosticRangeResource;
use Filament\Resources\Pages\ListRecords;

class ListDiagnosticRanges extends ListRecords
{
    protected static string $resource = DiagnosticRangeResource::class;

    /**
     * Sin acción de alta: los rangos son la clave de calificación y se cargan por
     * seeder o migración, no desde el panel (ver DiagnosticRangeResource::canCreate()).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
