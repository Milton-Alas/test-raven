<?php

namespace App\Filament\Resources\Candidates;

use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Candidates\Pages\ViewCandidate;
use App\Filament\Resources\Candidates\Schemas\CandidateForm;
use App\Filament\Resources\Candidates\Tables\CandidatesTable;
use App\Models\Candidate;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Candidatos';

    protected static ?string $modelLabel = 'Candidato';

    protected static ?string $pluralModelLabel = 'Candidatos';

    protected static string|UnitEnum|null $navigationGroup = 'Evaluaciones';

    protected static ?int $navigationSort = 10;
    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'reporter'], true);
    }

    public static function canCreate(): bool
    {
        return false; //Se registran los candidatos desde el frontend, no desde el admin
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
        return CandidateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CandidatesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos Personales')
                ->schema([
                    TextEntry::make('name')->label('Nombre')->weight('bold'),
                    TextEntry::make('email')->label('Email')->copyable()->icon('heroicon-m-envelope'),
                    TextEntry::make('age')->label('Edad'),
                    TextEntry::make('occupation')->label('Ocupación'),
                ])->columns(2),

            Section::make('Resultado Final')
                ->schema([
                    TextEntry::make('latestTestResult.total_score')->label('Puntaje Total')->placeholder('-'),
                    TextEntry::make('latestTestResult.percentile')->label('Percentil')->badge()->color('info')->placeholder('-'),
                    TextEntry::make('latestTestResult.diagnostic_label')->label('Diagnóstico')->weight('bold')->placeholder('-'),
                ])
                ->columns(3)
                ->visible(fn (Candidate $record): bool => (bool) $record->test_completed),
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
            'index' => ListCandidates::route('/'),
            'create' => CreateCandidate::route('/create'),
            'view' => ViewCandidate::route('/{record}'),
            'edit' => EditCandidate::route('/{record}/edit'),
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
