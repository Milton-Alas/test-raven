<?php

namespace App\Models;

use App\Models\Concerns\PreservesHistoricalData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticRange extends Model
{
    use HasFactory, PreservesHistoricalData;

    protected $fillable = [
        'percentile_min',
        'percentile_max',
        'range_number',
        'range_label',
        'diagnostic_label',
        'interpretation',
    ];

    /**
     * La tabla de rangos diagnósticos no tiene columna `is_active`, así que no hay
     * ningún campo que se pueda modificar sin alterar la interpretación de
     * resultados ya emitidos: el registro es completamente inmutable.
     *
     * @return array<int, string>
     */
    public static function historicalDataMutableAttributes(): array
    {
        return [];
    }

    public static function historicalDataLabel(): string
    {
        return 'rangos diagnósticos';
    }

    // Scopes
    public function scopeForPercentile($query, int $percentile)
    {
        return $query->where('percentile_min', '<=', $percentile)
            ->where('percentile_max', '>=', $percentile);
    }

    // Accessors
    public function getRangeRomanAttribute(): string
    {
        $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];

        return $romans[$this->range_number] ?? '';
    }
}
