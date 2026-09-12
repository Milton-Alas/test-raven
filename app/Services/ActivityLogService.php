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

    /**
     * Orden fijo de claves para el JSON de `properties` en exportaciones.
     * Cualquier clave que llegue en $properties y no esté en esta lista se
     * agrega al final, en el orden en que se recibió.
     */
    private const EXPORT_PROPERTY_ORDER = [
        'export_format',
        'filename',
        'exported_by_name',
        'exported_by_email',
        'exported_by_role',
        'candidate_dui_nit',
        'exported_at',
        'candidate_id',
        'candidate_name',
        'exported_by_id',
        'test_result_id',
        'candidate_email',
        'test_session_id',
        'diagnostic_label',
        'diagnostic_range',
    ];

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
     * @param  Model|null  $subject  Entidad exportada (ej. el TestResult).
     * @param  string  $format  Formato del archivo: 'pdf', 'csv', ... Se persiste
     *                          en `properties.export_format` para poder distinguir el
     *                          tipo de exportación sin depender del nombre del archivo.
     * @param  string  $filename  Nombre del archivo entregado.
     * @param  array  $properties  Metadatos adicionales del contexto exportado
     *                             (candidate_id, test_result_id, etc.).
     */
    public function logExport(
        Model $causer,
        ?Model $subject,
        string $format,
        string $filename,
        string $description,
        array $properties = []
    ): ActivityLog {
        $values = array_merge($properties, [
            'export_format' => $format,
            'filename' => $filename,
            'exported_by_name' => $causer->name ?? $causer->email ?? null,
            'exported_by_email' => $causer->email ?? null,
            'exported_by_role' => $causer->role ?? null,
            'exported_by_id' => $causer->getKey(),
            'exported_at' => now()->toIso8601String(),
        ]);

        $ordered = [];
        foreach (self::EXPORT_PROPERTY_ORDER as $key) {
            if (array_key_exists($key, $values)) {
                $ordered[$key] = $values[$key];
                unset($values[$key]);
            }
        }

        // Cualquier propiedad extra no contemplada arriba, al final.
        $ordered = array_merge($ordered, $values);

        return $this->log(
            causer: $causer,
            subject: $subject,
            event: self::EVENT_EXPORTED,
            description: $description,
            properties: $ordered,
        );
    }
}