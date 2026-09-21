<?php

namespace App\Filament\Resources\TestSeries\Pages;

use App\Filament\Resources\TestSeries\TestSeriesResource;
use Filament\Resources\Pages\ListRecords;

class ListTestSeries extends ListRecords
{
    protected static string $resource = TestSeriesResource::class;

    /**
     * Sin acción de alta: las series son instrumento normalizado y se cargan por
     * seeder o migración, no desde el panel (ver TestSeriesResource::canCreate()).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
