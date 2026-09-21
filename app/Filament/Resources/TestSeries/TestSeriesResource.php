<?php

namespace App\Filament\Resources\TestSeries;

use App\Filament\Resources\TestSeries\Pages\CreateTestSeries;
use App\Filament\Resources\TestSeries\Pages\EditTestSeries;
use App\Filament\Resources\TestSeries\Pages\ListTestSeries;
use App\Filament\Resources\TestSeries\Schemas\TestSeriesForm;
use App\Filament\Resources\TestSeries\Tables\TestSeriesTable;
use App\Models\TestSeries;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Series del test: instrumento normalizado, de solo lectura en el panel.
 *
 * Ningún rol —tampoco `admin`— crea, edita ni elimina desde aquí. La serie fija
 * el orden y el peso de los ítems con los que se calcularon resultados ya
 * emitidos, así que cualquier cambio real debe entrar por seeder o migración,
 * con control de versiones y rastro en el historial del repositorio; nunca por
 * un clic de UI sin rastro. El modelo lo refuerza con `PreservesHistoricalData`.
 */
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
        return in_array(Auth::user()?->role, ['admin', 'evaluador'], true);
    }

    /*
     * Alta, edición y borrado están cerrados para todos los roles (ver el
     * comentario de la clase): las series se cambian por seeder o migración,
     * nunca desde el panel.
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
