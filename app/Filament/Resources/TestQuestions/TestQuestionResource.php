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
        return Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === 'admin';
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
