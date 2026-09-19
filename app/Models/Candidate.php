<?php

namespace App\Models;

use App\Support\DuiNitCipher;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        // El identificador cifrado y su índice nunca se serializan: no aportan
        // nada al cliente y son datos sensibles (RNF-09.01).
        'dui_nit_hash',
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

    /**
     * RNF-09.01 — Cifrado en reposo e índice seguro.
     *
     * El identificador se cifra con AES-256-CBC antes de guardarlo, y su índice
     * determinista (`dui_nit_hash`) se recalcula en cada guardado para que nunca
     * queden desincronizados: la búsqueda y la validación de unicidad se apoyan
     * en ese índice, sin descifrar nada.
     */
    protected static function booted(): void
    {
        static::saving(function (self $candidate): void {
            $enClaro = $candidate->getAttribute('dui_nit');

            if ($enClaro === null || $enClaro === '') {
                return;
            }

            $candidate->setAttribute('dui_nit_hash', DuiNitCipher::hash($enClaro));
        });
    }

    /**
     * Busca un candidato por su identificador sin descifrar la columna.
     */
    public static function findByDuiNit(string $duiNit): ?self
    {
        $hash = DuiNitCipher::hash($duiNit);

        return $hash === null ? null : static::query()->where('dui_nit_hash', $hash)->first();
    }

    /**
     * Identificador enmascarado para listados y exportaciones.
     */
    public function getDuiNitMaskedAttribute(): ?string
    {
        return DuiNitCipher::mask($this->getAttribute('dui_nit'));
    }

    /**
     * El atributo `dui_nit` se lee y escribe cifrado (AES-256-CBC con APP_KEY).
     */
    protected function duiNit(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => DuiNitCipher::decrypt($value),
            set: fn (?string $value): ?string => DuiNitCipher::encrypt($value),
        );
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
        if ($this->age < 18) {
            return '12-17';
        }
        if ($this->age < 25) {
            return '18-24';
        }
        if ($this->age < 35) {
            return '25-34';
        }
        if ($this->age < 45) {
            return '35-44';
        }
        if ($this->age < 55) {
            return '45-54';
        }
        if ($this->age < 65) {
            return '55-64';
        }

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
