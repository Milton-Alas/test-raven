<?php

namespace App\Models;

use App\Models\Concerns\PreservesHistoricalData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerOption extends Model
{
    use HasFactory, PreservesHistoricalData;

    protected $fillable = [
        'test_question_id',
        'option_number',
        'option_image_path',
    ];

    protected function casts(): array
    {
        return [
            'option_number' => 'integer',
        ];
    }

    public static function historicalDataLabel(): string
    {
        return 'opciones de respuesta';
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
