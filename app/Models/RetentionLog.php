<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RNF-09.05 — Registro de las operaciones de retención.
 *
 * Cada ejecución de una política (disociar o suprimir) deja una fila aquí. Es la
 * evidencia que permite demostrar que las políticas configuradas se aplicaron
 * realmente, cuándo y con qué alcance.
 *
 * Deliberadamente no guarda datos personales: solo conteos y metadatos de la
 * operación.
 */
class RetentionLog extends Model
{
    protected $fillable = [
        'categoria',
        'accion',
        'dias_retencion',
        'fecha_corte',
        'registros_afectados',
        'detalle',
        'origen',
        'simulacion',
        'resultado',
        'error',
        'duracion_ms',
    ];

    protected function casts(): array
    {
        return [
            'fecha_corte' => 'datetime',
            'detalle' => 'array',
            'simulacion' => 'boolean',
            'dias_retencion' => 'integer',
            'registros_afectados' => 'integer',
            'duracion_ms' => 'integer',
        ];
    }

    // Scopes
    public function scopeDeCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeConError($query)
    {
        return $query->where('resultado', 'error');
    }

    public function scopeReales($query)
    {
        return $query->where('simulacion', false);
    }
}
