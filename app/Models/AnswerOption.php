<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_question_id',
        'option_number',
        'text',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    // Relaciones
    public function question()
    {
        return $this->belongsTo(TestQuestion::class);
    }

    public function testAnswers()
    {
        return $this->hasMany(TestAnswer::class);
    }
}
