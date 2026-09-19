<?php

namespace App\Models\Concerns;

use App\Exceptions\HistoricalDataException;
use App\Models\TestSession;
use Illuminate\Support\Facades\Schema;

/**
 * Protege el contenido del test que ya forma parte de resultados emitidos.
 *
 * Reglas que aplica al modelo que use el trait:
 *
 *  1. **No se puede borrar.** Las respuestas de los candidatos apuntan a
 *     `test_questions` con `onDelete('cascade')`, así que eliminar una pregunta
 *     borra las respuestas de todos los que la respondieron, y un recálculo
 *     posterior daría un puntaje sobre menos ítems (silenciosamente incorrecto).
 *  2. **No se puede editar el contenido** que determina el puntaje, el percentil
 *     o el diagnóstico. Solo se permiten los campos que devuelve
 *     `historicalDataMutableAttributes()` —típicamente `is_active`— para poder
 *     retirar un ítem sin destruir la historia.
 *  3. **Solo se puede crear contenido si no hay un test en curso**, porque un
 *     candidato que está respondiendo vería un instrumento distinto a mitad de
 *     la prueba.
 *
 * El trait es la última línea de defensa: cubre el panel de administración, la
 * consola (`artisan tinker`) y cualquier código futuro. Los seeders siguen
 * funcionando porque la creación se permite cuando no hay sesiones activas.
 */
trait PreservesHistoricalData
{
    /**
     * Atributos que sí pueden modificarse después de la creación.
     *
     * El modelo que use el trait puede sobreescribir este método.
     *
     * @return array<int, string>
     */
    public static function historicalDataMutableAttributes(): array
    {
        return ['is_active'];
    }

    /**
     * Nombre legible del modelo para los mensajes de error.
     */
    public static function historicalDataLabel(): string
    {
        return class_basename(static::class);
    }

    public static function bootPreservesHistoricalData(): void
    {
        static::updating(function (self $model): void {
            $permitidos = static::historicalDataMutableAttributes();

            $bloqueados = array_values(array_filter(
                array_keys($model->getDirty()),
                fn (string $atributo): bool => ! in_array($atributo, $permitidos, true)
            ));

            if ($bloqueados !== []) {
                throw HistoricalDataException::updateBlocked(
                    static::historicalDataLabel(),
                    $bloqueados,
                    'Para retirarlo del test sin destruir la historia, desactívalo con el campo "Activo".'
                );
            }
        });

        static::deleting(function (self $model): void {
            throw HistoricalDataException::deletionBlocked(
                static::historicalDataLabel(),
                'Si el objetivo es que no se use más, desactívalo con el campo "Activo".'
            );
        });

        static::creating(function (): void {
            if (static::hasTestInProgress()) {
                throw HistoricalDataException::creationBlocked(static::historicalDataLabel());
            }
        });
    }

    /**
     * ¿Hay alguna sesión de test en curso ahora mismo?
     *
     * Si la tabla no existe (por ejemplo durante una migración) no se bloquea
     * nada: la protección aplica al uso normal, no a la construcción del esquema.
     */
    public static function hasTestInProgress(): bool
    {
        if (! Schema::hasTable('test_sessions')) {
            return false;
        }

        return TestSession::query()
            ->whereIn('status', ['not_started', 'in_progress', 'paused'])
            ->exists();
    }
}
