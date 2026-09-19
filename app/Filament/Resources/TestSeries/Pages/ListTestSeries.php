<?php

namespace App\Filament\Resources\TestSeries\Pages;

use App\Filament\Resources\TestSeries\TestSeriesResource;
use App\Filament\Support\HistoricalContent;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTestSeries extends ListRecords
{
    protected static string $resource = TestSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // Cargar reactivos es legítimo, pero no mientras alguien está
                // respondiendo: vería un instrumento distinto a mitad del test.
                ->disabled(fn (): bool => HistoricalContent::creationLocked())
                ->tooltip(fn (): ?string => HistoricalContent::creationLocked()
                    ? HistoricalContent::creationLockedNotice()
                    : null),
        ];
    }
}
