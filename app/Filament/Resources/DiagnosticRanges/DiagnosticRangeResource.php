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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;


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
