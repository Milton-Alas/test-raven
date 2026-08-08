<?php

namespace App\Filament\Resources\TestSessions\Pages;

use App\Filament\Resources\TestSessions\TestSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListTestSessions extends ListRecords
{
    protected static string $resource = TestSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
