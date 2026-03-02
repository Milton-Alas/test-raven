<?php

namespace App\Filament\Resources\TestSessions;

use App\Filament\Resources\TestSessions\Pages\CreateTestSession;
use App\Filament\Resources\TestSessions\Pages\EditTestSession;
use App\Filament\Resources\TestSessions\Pages\ListTestSessions;
use App\Filament\Resources\TestSessions\Pages\ViewTestSession;
use App\Filament\Resources\TestSessions\Schemas\TestSessionForm;
use App\Filament\Resources\TestSessions\Tables\TestSessionsTable;
use App\Models\TestSession;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TestSessionResource extends Resource
{
    protected static ?string $model = TestSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';//'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Sesiones de Test';

    protected static ?string $modelLabel = 'Sesión';

    protected static ?string $pluralModelLabel = 'Sesiones de Test';

    protected static string|UnitEnum|null $navigationGroup = 'Evaluaciones';

    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'reporter'], true);
    }

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
        return TestSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestSessionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Estado de la Evaluación')
                ->icon('heroicon-m-signal')
                ->schema([
                    TextEntry::make('candidate.name')
                        ->label('Candidato')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-user')
                        ->placeholder('Sin candidato'),

                    TextEntry::make('status')
                        ->label('Estado Actual')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'in_progress' => 'warning',
                            'completed' => 'success',
                            'timeout' => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('progress_percentage')
                        ->label('Progreso del Test')
                        ->state(fn (TestSession $record): string => round($record->progress_percentage) . '%')
                        ->color(fn (TestSession $record): string => match (true) {
                            $record->progress_percentage >= 100 => 'success',
                            $record->progress_percentage >= 50 => 'info',
                            default => 'warning',
                        })
                        ->weight(FontWeight::Bold),

                    TextEntry::make('started_at')
                        ->label('Inicio de Sesión')
                        ->dateTime('d/m/Y H:i:s')
                        ->placeholder('-'),

                    TextEntry::make('completed_at')
                        ->label('Finalización')
                        ->dateTime('d/m/Y H:i:s')
                        ->placeholder('Aún en curso...')
                        ->color('gray'),
                ])
                ->columns(3),

            Section::make('Trazabilidad Técnica')
                ->description('Datos de conexión y dispositivo para auditoría.')
                ->icon('heroicon-m-shield-check')
                ->schema([
                    TextEntry::make('ip_address')
                        ->label('Dirección IP')
                        ->icon('heroicon-m-globe-alt')
                        ->copyable()
                        ->copyMessage('IP copiada')
                        ->placeholder('Desconocida'),

                    TextEntry::make('browser_info.browser')
                        ->label('Navegador Detectado')
                        ->state(fn (TestSession $record): string => data_get($record->browser_info, 'browser', 'Desconocido')),

                    TextEntry::make('browser_info.platform')
                        ->label('Sistema Operativo / Plataforma')
                        ->state(fn (TestSession $record): string => data_get($record->browser_info, 'platform', 'Desconocido')),
                ])
                ->columns(3)
                ->collapsed(),
        ]);
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
            'index' => ListTestSessions::route('/'),
            'create' => CreateTestSession::route('/create'),
            'view' => ViewTestSession::route('/{record}'),
            'edit' => EditTestSession::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
