<?php

namespace App\Filament\Resources\PercentileTables\Pages;

use App\Filament\Resources\PercentileTables\PercentileTableResource;
use Filament\Resources\Pages\EditRecord;

class EditPercentileTable extends EditRecord
{
    protected static string $resource = PercentileTableResource::class;

    /**
     * Sin acciones de borrado: el contenido del test no se elimina porque
     * invalidaría los resultados ya calculados (ver PreservesHistoricalData).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
