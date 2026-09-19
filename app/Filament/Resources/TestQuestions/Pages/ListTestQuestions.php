<?php

namespace App\Filament\Resources\TestQuestions\Pages;

use App\Filament\Resources\TestQuestions\TestQuestionResource;
use App\Filament\Support\HistoricalContent;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTestQuestions extends ListRecords
{
    protected static string $resource = TestQuestionResource::class;

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
