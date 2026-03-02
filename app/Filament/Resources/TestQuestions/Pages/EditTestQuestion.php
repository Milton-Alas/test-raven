<?php

namespace App\Filament\Resources\TestQuestions\Pages;

use App\Filament\Resources\TestQuestions\TestQuestionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTestQuestion extends EditRecord
{
    protected static string $resource = TestQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
