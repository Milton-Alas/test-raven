<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'percentile_min',
        'percentile_max',
        'range_number',
        'range_label',
        'diagnostic_label',
        'interpretation',
    ];

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
