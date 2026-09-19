<?php

namespace App\Filament\Resources\TestQuestions\Pages;

use App\Filament\Resources\TestQuestions\TestQuestionResource;
use Filament\Resources\Pages\EditRecord;

class EditTestQuestion extends EditRecord
{
    protected static string $resource = TestQuestionResource::class;

    /**
     * Sin acciones de borrado: el contenido del test no se elimina porque
     * invalidaría los resultados ya calculados (ver PreservesHistoricalData).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
