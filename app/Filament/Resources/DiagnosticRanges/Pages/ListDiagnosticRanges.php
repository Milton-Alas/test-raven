<?php

namespace App\Filament\Resources\DiagnosticRanges\Pages;

use App\Filament\Resources\DiagnosticRanges\DiagnosticRangeResource;
use App\Filament\Support\HistoricalContent;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiagnosticRanges extends ListRecords
{
    protected static string $resource = DiagnosticRangeResource::class;

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
