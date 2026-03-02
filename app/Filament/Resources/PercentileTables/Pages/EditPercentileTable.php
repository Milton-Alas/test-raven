<?php

namespace App\Filament\Resources\PercentileTables\Pages;

use App\Filament\Resources\PercentileTables\PercentileTableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPercentileTable extends EditRecord
{
    protected static string $resource = PercentileTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
