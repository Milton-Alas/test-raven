<?php

namespace App\Filament\Resources\TestSeries\Pages;

use App\Filament\Resources\TestSeries\TestSeriesResource;
use Filament\Resources\Pages\EditRecord;

class EditTestSeries extends EditRecord
{
    protected static string $resource = TestSeriesResource::class;

    /**
     * Sin acciones de borrado: el contenido del test no se elimina porque
     * invalidaría los resultados ya calculados (ver PreservesHistoricalData).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
