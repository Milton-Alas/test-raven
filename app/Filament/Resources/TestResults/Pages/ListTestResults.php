<?php

namespace App\Filament\Resources\TestResults\Pages;

use App\Filament\Resources\TestResults\TestResultResource;
use Filament\Resources\Pages\ListRecords;

class ListTestResults extends ListRecords
{
    protected static string $resource = TestResultResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
