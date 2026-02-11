<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_series_id',
        'question_number',
        'global_order',
        'matrix_image_path',
        'correct_answer',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'correct_answer' => 'integer',
        ];
    }

    // Relaciones
    public function series()
    {
        return $this->belongsTo(TestSeries::class, 'test_series_id');
    }

    public function answerOptions()
    {
        return $this->hasMany(AnswerOption::class)->orderBy('option_number');
    }

    public function testAnswers()
    {
        return $this->hasMany(TestAnswer::class);
    }

    // Accessors
    public function getMatrixImageUrlAttribute(): string
    {
        return asset('storage/' . $this->matrix_image_path);
    }

    public function getFullCodeAttribute(): string
    {
        return $this->series->code . $this->question_number;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('global_order');
    }

}
