<?php

namespace App\Filament\Resources\TestSeries\Pages;

use App\Filament\Resources\TestSeries\TestSeriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTestSeries extends EditRecord
{
    protected static string $resource = TestSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
