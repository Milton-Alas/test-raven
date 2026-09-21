<?php

namespace App\Filament\Resources\DiagnosticRanges;

use App\Filament\Resources\DiagnosticRanges\Pages\CreateDiagnosticRange;
use App\Filament\Resources\DiagnosticRanges\Pages\EditDiagnosticRange;
use App\Filament\Resources\DiagnosticRanges\Pages\ListDiagnosticRanges;
use App\Filament\Resources\DiagnosticRanges\Schemas\DiagnosticRangeForm;
use App\Filament\Resources\DiagnosticRanges\Tables\DiagnosticRangesTable;
use App\Models\DiagnosticRange;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Rangos diagnósticos (I–V): clave de calificación, de solo lectura en el panel.
 *
 * Ningún rol —tampoco `admin`— crea, edita ni elimina desde aquí. El rango es la
 * interpretación oficial que se emitió junto a cada resultado, así que cualquier
 * cambio real debe entrar por seeder o migración, con control de versiones y
 * rastro en el historial del repositorio; nunca por un clic de UI sin rastro. El
 * modelo lo refuerza con `PreservesHistoricalData`.
 */
class DiagnosticRangeResource extends Resource
{
    protected static ?string $model = DiagnosticRange::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Rangos de Diagnóstico';

    protected static ?string $modelLabel = 'Rango de Diagnóstico';

    protected static ?string $pluralModelLabel = 'Rangos de Diagnóstico';

    protected static string|UnitEnum|null $navigationGroup = 'Configuración Test';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'evaluador'], true);
    }

    /*
     * Alta, edición y borrado están cerrados para todos los roles (ver el
     * comentario de la clase): los rangos se cambian por seeder o migración,
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
        return DiagnosticRangeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiagnosticRangesTable::configure($table);
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
            'index' => ListDiagnosticRanges::route('/'),
            'create' => CreateDiagnosticRange::route('/create'),
            'edit' => EditDiagnosticRange::route('/{record}/edit'),
        ];
    }
}
