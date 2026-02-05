<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'test_question_id',
        'selected_answer',
        'is_correct',
        'time_spent',
        'answered_at',
        'attempt_number',
        'was_changed',
        'interaction_log',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'was_changed' => 'boolean',
            'answered_at' => 'datetime',
            'interaction_log' => 'array',
        ];
    }

    // Relaciones
    public function testSession()
    {
        return $this->belongsTo(TestSession::class);
    }

    public function testQuestion()
    {
        return $this->belongsTo(TestQuestion::class);
    }

    public function answerOption()
    {
        return $this->belongsTo(AnswerOption::class, 'selected_answer');
    }

    // Scopes
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    public function scopeAnswered($query)
    {
        return $query->whereNotNull('selected_answer');
    }

    public function scopeUnanswered($query)
    {
        return $query->whereNull('selected_answer');
    }
}
