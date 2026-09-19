<?php

namespace App\Filament\Resources\TestResults;

use App\Filament\Resources\TestResults\Pages\CreateTestResult;
use App\Filament\Resources\TestResults\Pages\EditTestResult;
use App\Filament\Resources\TestResults\Pages\ListTestResults;
use App\Filament\Resources\TestResults\Pages\ViewTestResult;
use App\Filament\Resources\TestResults\Schemas\TestResultForm;
use App\Filament\Resources\TestResults\Tables\TestResultsTable;
use App\Models\TestResult;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TestResultResource extends Resource
{
    protected static ?string $model = TestResult::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Resultados de Tests';
    protected static ?string $modelLabel = 'Resultado de Test';
    protected static ?string $pluralModelLabel = 'Resultados de Tests';
    protected static string|UnitEnum|null $navigationGroup = 'Evaluaciones';
    protected static ?int $navigationSort = 12;

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

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return TestResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestResultsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del Evaluado')
                ->icon('heroicon-m-user-circle')
                ->schema([
                    TextEntry::make('candidate.name')
                        ->label('Nombre Completo')
                        ->weight(FontWeight::Bold),

                    TextEntry::make('candidate.age')
                        ->label('Edad')
                        ->suffix(' años'),

                    TextEntry::make('testSession.started_at')
                        ->label('Fecha de Aplicación')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('-'),

                    TextEntry::make('total_time_formatted')
                        ->label('Tiempo de Ejecución')
                        ->icon('heroicon-m-clock')
                        ->color('info'),
                ])
                ->columns(4),

            Section::make('Puntajes por Serie')
                ->description('Desglose de aciertos por cada sección del Test de Raven (Máximo 12 por serie).')
                ->schema([
                    TextEntry::make('series_a_score')->label('Serie A')->suffix(' / 12'),
                    TextEntry::make('series_b_score')->label('Serie B')->suffix(' / 12'),
                    TextEntry::make('series_c_score')->label('Serie C')->suffix(' / 12'),
                    TextEntry::make('series_d_score')->label('Serie D')->suffix(' / 12'),
                    TextEntry::make('series_e_score')->label('Serie E')->suffix(' / 12'),

                    TextEntry::make('total_score')
                        ->label('PUNTAJE TOTAL')
                        ->weight(FontWeight::ExtraBold)
                        ->color('primary')
                        ->suffix(' / 60'),
                ])
                ->columns(6),

            Section::make('Diagnóstico y Clasificación')
                ->icon('heroicon-m-clipboard-document-check')
                ->schema([
                    TextEntry::make('percentile')
                        ->label('Percentil')
                        ->badge()
                        ->size(TextSize::Large)
                        ->color(fn ($state) => match (true) {
                            $state >= 95 => 'success',
                            $state >= 75 => 'info',
                            $state >= 25 => 'warning',
                            default => 'danger',
                        }),

                    TextEntry::make('diagnostic_range_roman')
                        ->label('Rango')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('diagnostic_label')
                        ->label('Clasificación Final')
                        ->weight(FontWeight::Bold)
                        ->size(TextSize::Large)
                        ->color('primary')
                        ->placeholder('-'),
                ])
                ->columns(3),
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
            'index' => ListTestResults::route('/'),
            'create' => CreateTestResult::route('/create'),
            'view' => ViewTestResult::route('/{record}'),
            'edit' => EditTestResult::route('/{record}/edit'),
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
