<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * Evento registrado cuando un usuario descarga o genera una exportación.
     */
    public const EVENT_EXPORTED = 'exported';

    public function log(
        ?Model $causer,
        ?Model $subject,
        string $event,
        ?string $description = null,
        array $properties = [],
        array $changes = []
    ): ActivityLog {
        return ActivityLog::create([
            'causer_type' => $causer ? get_class($causer) : null,
            'causer_id' => $causer?->id,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Registra una exportación para poder auditar quién descargó qué.
     *
     * @param  Model  $causer  Usuario que descarga (admin o reporter).
     * @param  Model|null  $subject  Entidad exportada. En una exportación por
     *                               lotes, la referencia al conjunto.
     * @param  string  $format  Formato del archivo: 'pdf', 'csv', ...
     * @param  string  $filename  Nombre del archivo entregado.
     * @param  array  $properties  Metadatos adicionales (contadores, filtros, ids).
     */
    public function logExport(
        Model $causer,
        ?Model $subject,
        string $format,
        string $filename,
        string $description,
        array $properties = []
    ): ActivityLog {
        return $this->log(
            causer: $causer,
            subject: $subject,
            event: self::EVENT_EXPORTED,
            description: $description,
            properties: array_merge([
                'export_format' => $format,
                'filename' => $filename,
                'exported_at' => now()->toIso8601String(),
                'exported_by_id' => $causer->getKey(),
                'exported_by_name' => $causer->name ?? $causer->email ?? null,
                'exported_by_role' => $causer->role ?? null,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ], $properties),
        );
    }
}
