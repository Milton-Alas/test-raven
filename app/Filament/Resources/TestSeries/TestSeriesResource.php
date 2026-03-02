<?php

namespace App\Filament\Resources\TestSeries;

use App\Filament\Resources\TestSeries\Pages\CreateTestSeries;
use App\Filament\Resources\TestSeries\Pages\EditTestSeries;
use App\Filament\Resources\TestSeries\Pages\ListTestSeries;
use App\Filament\Resources\TestSeries\Schemas\TestSeriesForm;
use App\Filament\Resources\TestSeries\Tables\TestSeriesTable;
use App\Models\TestSeries;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TestSeriesResource extends Resource
{
    protected static ?string $model = TestSeries::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Series de Tests';

    protected static ?string $modelLabel = 'Serie de Test';

    protected static ?string $pluralModelLabel = 'Series de Tests';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración Test';

    protected static ?int $navigationSort = 1;

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
        return TestSeriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestSeriesTable::configure($table);
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
            'index' => ListTestSeries::route('/'),
            'create' => CreateTestSeries::route('/create'),
            'edit' => EditTestSeries::route('/{record}/edit'),
        ];
    }
}
