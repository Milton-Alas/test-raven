<?php

namespace App\Filament\Resources\TestSessions\Pages;

use App\Filament\Resources\TestSessions\TestSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTestSession extends EditRecord
{
    protected static string $resource = TestSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
