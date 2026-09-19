<?php

namespace App\Filament\Resources\PercentileTables\Pages;

use App\Filament\Resources\PercentileTables\PercentileTableResource;
use App\Filament\Support\HistoricalContent;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPercentileTables extends ListRecords
{
    protected static string $resource = PercentileTableResource::class;

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
