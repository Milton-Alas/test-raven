<?php

namespace App\Filament\Resources\PercentileTables\Pages;

use App\Filament\Resources\PercentileTables\PercentileTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPercentileTables extends ListRecords
{
    protected static string $resource = PercentileTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
