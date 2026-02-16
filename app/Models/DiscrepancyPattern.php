<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscrepancyPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_score',
        'expected_a',
        'expected_b',
        'expected_c',
        'expected_d',
        'expected_e',
    ];

    // Scopes
    public function scopeForScore($query, int $totalScore)
    {
        return $query->where('total_score', $totalScore);
    }

    // Accessors
    public function getExpectedArrayAttribute(): array
    {
        return [
            'A' => $this->expected_a,
            'B' => $this->expected_b,
            'C' => $this->expected_c,
            'D' => $this->expected_d,
            'E' => $this->expected_e,
        ];
    }
}
