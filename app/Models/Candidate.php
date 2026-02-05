<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable; // <-- Importar clase correcta
use Illuminate\Notifications\Notifiable;

// Extender de Authenticatable en lugar de Model
class Candidate extends Authenticatable
{
     use HasFactory, Notifiable, SoftDeletes;

    protected $guard = 'candidate';

    protected $fillable = [
        'name',
        'email',
        'dui_nit', // Añadido para asignación masiva
        'password',
        'age',
        'occupation',
        'education_level',
        'test_completed',
        'test_started_at',
        'test_completed_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'test_completed' => 'boolean',
            'test_started_at' => 'datetime',
            'test_completed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relaciones
    public function testSession()
    {
        return $this->hasOne(TestSession::class);
    }

    public function testResults()
    {
        return $this->hasMany(TestResult::class);
    }

    public function latestTestResult()
    {
        return $this->hasOne(TestResult::class)->latestOfMany();
    }

    // Accessors
    public function getAgeRangeAttribute(): string
    {
        if ($this->age < 18) return '12-17';
        if ($this->age < 25) return '18-24';
        if ($this->age < 35) return '25-34';
        if ($this->age < 45) return '35-44';
        if ($this->age < 55) return '45-54';
        if ($this->age < 65) return '55-64';
        return '65+';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTestCompleted($query)
    {
        return $query->where('test_completed', true);
    }

    public function scopeTestPending($query)
    {
        return $query->where('test_completed', false);
    }
}
