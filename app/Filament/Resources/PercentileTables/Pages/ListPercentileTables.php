<?php

namespace App\Filament\Resources\PercentileTables\Pages;

use App\Filament\Resources\PercentileTables\PercentileTableResource;
use Filament\Resources\Pages\ListRecords;

class ListPercentileTables extends ListRecords
{
    protected static string $resource = PercentileTableResource::class;

    /**
     * Sin acción de alta: el baremo es instrumento normalizado y se carga por
     * seeder o migración, no desde el panel (ver PercentileTableResource::canCreate()).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
