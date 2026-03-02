<?php

namespace App\Filament\Resources\DiagnosticRanges\Pages;

use App\Filament\Resources\DiagnosticRanges\DiagnosticRangeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiagnosticRange extends EditRecord
{
    protected static string $resource = DiagnosticRangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
