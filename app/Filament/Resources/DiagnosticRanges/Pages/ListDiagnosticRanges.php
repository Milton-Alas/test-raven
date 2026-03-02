<?php

namespace App\Filament\Resources\DiagnosticRanges\Pages;

use App\Filament\Resources\DiagnosticRanges\DiagnosticRangeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiagnosticRanges extends ListRecords
{
    protected static string $resource = DiagnosticRangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
