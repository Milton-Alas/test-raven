<?php

namespace App\Filament\Resources\PercentileTables;

use App\Filament\Resources\PercentileTables\Pages\CreatePercentileTable;
use App\Filament\Resources\PercentileTables\Pages\EditPercentileTable;
use App\Filament\Resources\PercentileTables\Pages\ListPercentileTables;
use App\Filament\Resources\PercentileTables\Schemas\PercentileTableForm;
use App\Filament\Resources\PercentileTables\Tables\PercentileTablesTable;
use App\Models\PercentileTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PercentileTableResource extends Resource
{
    protected static ?string $model = PercentileTable::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Tabla de Percentiles';

    protected static ?string $modelLabel = 'Percentil';

    protected static ?string $pluralModelLabel = 'Tabla de Percentiles';

    protected static string|UnitEnum|null $navigationGroup = 'Configuración Test';

    protected static ?int $navigationSort = 3;

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
        return PercentileTableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PercentileTablesTable::configure($table);
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
            'index' => ListPercentileTables::route('/'),
            'create' => CreatePercentileTable::route('/create'),
            'edit' => EditPercentileTable::route('/{record}/edit'),
        ];
    }
}
