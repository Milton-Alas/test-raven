<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'current_question_id',
        'started_at',
        'completed_at',
        'last_activity_at',
        'elapsed_time',
        'time_limit',
        'remaining_time',
        'status',
        'ip_address',
        'user_agent',
        'browser_info',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'browser_info' => 'array',
        ];
    }

    // Relaciones
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function currentQuestion()
    {
        return $this->belongsTo(TestQuestion::class, 'current_question_id');
    }

    public function testAnswers()
    {
        return $this->hasMany(TestAnswer::class);
    }

    public function testResult()
    {
        return $this->hasOne(TestResult::class);
    }

    // Accessors
    public function getIsInProgressAttribute(): bool
    {
        return $this->status === 'in_progress';
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'timeout']);
    }

    public function getRemainingTimeInMinutesAttribute(): int
    {
        return (int) ceil($this->remaining_time / 60);
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalQuestions = 60;
        $answeredQuestions = $this->testAnswers()->count();
        return ($answeredQuestions / $totalQuestions) * 100;
    }

    // Scopes
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'timeout']);
    }
}
