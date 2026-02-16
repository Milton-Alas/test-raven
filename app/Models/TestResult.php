<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'test_session_id',
        'candidate_id',
        'series_a_score',
        'series_b_score',
        'series_c_score',
        'series_d_score',
        'series_e_score',
        'total_score',
        'percentile',
        'diagnostic_range',
        'diagnostic_label',
        'is_valid',
        'validity_notes',
        'score_distribution',
        'total_time_seconds',
        'average_time_per_question',
        'calculated_at',
        'calculated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'score_distribution' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    // Relaciones
    public function testSession()
    {
        return $this->belongsTo(TestSession::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function calculatedBy()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    // Accessors
    public function getDiagnosticRangeRomanAttribute(): string
    {
        $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
        return $romans[$this->diagnostic_range] ?? '';
    }

    public function getTotalTimeFormattedAttribute(): string
    {
        $minutes = floor($this->total_time_seconds / 60);
        $seconds = $this->total_time_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    public function scopeByDiagnosticRange($query, int $range)
    {
        return $query->where('diagnostic_range', $range);
    }

    public function scopeByPercentileRange($query, int $min, int $max)
    {
        return $query->whereBetween('percentile', [$min, $max]);
    }
}
