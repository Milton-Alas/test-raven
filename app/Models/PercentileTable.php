<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PercentileTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'age_min',
        'age_max',
        'raw_score',
        'percentile',
        'norm_group',
        'norm_year',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAge($query, int $age)
    {
        return $query->where('age_min', '<=', $age)
                     ->where('age_max', '>=', $age);
    }

    public function scopeForScore($query, int $score)
    {
        return $query->where('raw_score', $score);
    }

    public function scopeForNormGroup($query, string $normGroup = 'montevideo')
    {
        return $query->where('norm_group', $normGroup);
    }

    // Accessors
    public function getAgeRangeAttribute(): string
    {
        return "{$this->age_min}-{$this->age_max}";
    }
}
