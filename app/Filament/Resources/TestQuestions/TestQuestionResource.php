<?php

namespace App\Filament\Resources\TestQuestions;

use App\Filament\Resources\TestQuestions\Pages\CreateTestQuestion;
use App\Filament\Resources\TestQuestions\Pages\EditTestQuestion;
use App\Filament\Resources\TestQuestions\Pages\ListTestQuestions;
use App\Filament\Resources\TestQuestions\Schemas\TestQuestionForm;
use App\Filament\Resources\TestQuestions\Tables\TestQuestionsTable;
use App\Models\TestQuestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Banco de reactivos: instrumento normalizado, de solo lectura en el panel.
 *
 * Ningún rol —tampoco `admin`— crea, edita ni elimina desde aquí. El contenido
 * de un reactivo determina el puntaje y el diagnóstico de tests ya rendidos, así
 * que cualquier cambio real debe entrar por seeder o migración, con control de
 * versiones y rastro en el historial del repositorio; nunca por un clic de UI
 * sin rastro. El modelo lo refuerza con `PreservesHistoricalData`.
 */
class TestQuestionResource extends Resource
{
    protected static ?string $model = TestQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Preguntas / Reactivos';

    protected static ?string $modelLabel = 'Pregunta';

    protected static ?string $pluralModelLabel = 'Preguntas de la Serie';

    protected static string|UnitEnum|null $navigationGroup = 'Configuración Test';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'evaluador'], true);
    }

    /*
     * Alta, edición y borrado están cerrados para todos los roles (ver el
     * comentario de la clase): el banco de reactivos se cambia por seeder o
     * migración, nunca desde el panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return TestQuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestQuestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestQuestions::route('/'),
            'create' => CreateTestQuestion::route('/create'),
            'edit' => EditTestQuestion::route('/{record}/edit'),
        ];
    }
}
