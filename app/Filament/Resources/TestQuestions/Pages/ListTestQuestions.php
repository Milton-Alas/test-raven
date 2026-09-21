<?php

namespace App\Filament\Resources\TestQuestions\Pages;

use App\Filament\Resources\TestQuestions\TestQuestionResource;
use Filament\Resources\Pages\ListRecords;

class ListTestQuestions extends ListRecords
{
    protected static string $resource = TestQuestionResource::class;

    /**
     * Sin acción de alta: el banco de reactivos es instrumento normalizado y se
     * carga por seeder o migración, no desde el panel (ver TestQuestionResource::canCreate()).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
