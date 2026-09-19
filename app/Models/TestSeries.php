<?php

namespace App\Models;

use App\Models\Concerns\PreservesHistoricalData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSeries extends Model
{
    use HasFactory, PreservesHistoricalData;

    protected $fillable = [
        'code',
        'order',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relaciones
    public function questions()
    {
        return $this->hasMany(TestQuestion::class)->orderBy('question_number');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
