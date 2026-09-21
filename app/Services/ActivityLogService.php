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
     * Evento registrado al dar de alta una cuenta del panel de administración.
     */
    public const EVENT_USER_CREATED = 'user_created';

    /**
     * Evento registrado al cambiar el rol de una cuenta del panel.
     */
    public const EVENT_USER_ROLE_CHANGED = 'user_role_changed';

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

    /**
     * Orden fijo de claves para el JSON de `properties` en los eventos de cuentas
     * del panel (alta y cambio de rol), con el mismo criterio que las
     * exportaciones: primero el contexto que se lee siempre, después el detalle.
     */
    private const USER_PROPERTY_ORDER = [
        'changed_by_id',
        'changed_by_name',
        'changed_by_email',
        'changed_by_role',
        'user_id',
        'user_name',
        'user_email',
        'role',
        'role_from',
        'role_to',
        'changed_at',
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

        return $this->log(
            causer: $causer,
            subject: $subject,
            event: self::EVENT_EXPORTED,
            description: $description,
            properties: $this->orderProperties($values, self::EXPORT_PROPERTY_ORDER),
        );
    }

    /**
     * Registra el alta de una cuenta del panel, con el rol que se le asignó.
     *
     * El rol es el dato que importa: `admin` abre la administración completa y
     * `evaluador` o `reporter` solo la consulta, así que saber qué cuenta se creó,
     * con qué rol y quién la creó es la trazabilidad mínima de la gestión de
     * accesos. Nunca se guarda la contraseña ni su hash.
     *
     * @param  Model|null  $causer  Usuario del panel que crea la cuenta; null si el
     *                              alta ocurre fuera del panel (consola o seeder).
     * @param  Model  $subject  Cuenta creada.
     * @param  array  $properties  Metadatos adicionales del alta.
     */
    public function logUserCreated(?Model $causer, Model $subject, array $properties = []): ActivityLog
    {
        $role = (string) $subject->role;

        return $this->logUserEvent(
            event: self::EVENT_USER_CREATED,
            causer: $causer,
            subject: $subject,
            description: "Cuenta del panel creada {$this->actor($causer)}: {$this->account($subject)} con rol «{$role}».",
            values: array_merge($properties, ['role' => $role]),
            changes: ['role' => ['from' => null, 'to' => $role]],
        );
    }

    /**
     * Registra el cambio de rol de una cuenta del panel.
     *
     * Se guarda el rol anterior y el nuevo tanto en `properties` (para leerlo sin
     * interpretar el JSON) como en `changes` (con la forma antes/después), igual
     * que el resto de eventos auditados.
     *
     * @param  Model|null  $causer  Usuario del panel que hace el cambio; null si
     *                              ocurre fuera del panel.
     * @param  Model  $subject  Cuenta modificada.
     * @param  string  $roleFrom  Rol anterior.
     * @param  string  $roleTo  Rol nuevo.
     * @param  array  $properties  Metadatos adicionales del cambio.
     */
    public function logUserRoleChange(
        ?Model $causer,
        Model $subject,
        string $roleFrom,
        string $roleTo,
        array $properties = []
    ): ActivityLog {
        return $this->logUserEvent(
            event: self::EVENT_USER_ROLE_CHANGED,
            causer: $causer,
            subject: $subject,
            description: "Rol cambiado {$this->actor($causer)}: {$this->account($subject)} pasa de «{$roleFrom}» a «{$roleTo}».",
            values: array_merge($properties, [
                'role_from' => $roleFrom,
                'role_to' => $roleTo,
            ]),
            changes: ['role' => ['from' => $roleFrom, 'to' => $roleTo]],
        );
    }

    /**
     * Escribe un evento del ciclo de vida de una cuenta del panel.
     *
     * Añade los metadatos comunes (quién lo hizo, cuándo y sobre qué cuenta) y
     * ordena las claves de `properties`.
     */
    private function logUserEvent(
        string $event,
        ?Model $causer,
        Model $subject,
        string $description,
        array $values,
        array $changes
    ): ActivityLog {
        $values = array_merge($values, [
            'changed_by_id' => $causer?->getKey(),
            'changed_by_name' => $causer?->name ?? $causer?->email ?? null,
            'changed_by_email' => $causer?->email ?? null,
            'changed_by_role' => $causer?->role ?? null,
            'user_id' => $subject->getKey(),
            'user_name' => $subject->name ?? null,
            'user_email' => $subject->email ?? null,
            'changed_at' => now()->toIso8601String(),
        ]);

        return $this->log(
            causer: $causer,
            subject: $subject,
            event: $event,
            description: $description,
            properties: $this->orderProperties($values, self::USER_PROPERTY_ORDER),
            changes: $changes,
        );
    }

    /**
     * Reordena `properties`: primero las claves conocidas en el orden indicado y,
     * al final, cualquier clave extra en el orden en que se recibió.
     */
    private function orderProperties(array $values, array $order): array
    {
        $ordered = [];

        foreach ($order as $key) {
            if (array_key_exists($key, $values)) {
                $ordered[$key] = $values[$key];
                unset($values[$key]);
            }
        }

        return array_merge($ordered, $values);
    }

    /**
     * Quién ejecutó la acción, para la descripción legible.
     */
    private function actor(?Model $causer): string
    {
        if (! $causer) {
            return 'fuera del panel (sin usuario autenticado)';
        }

        return 'por '.($causer->name ?? $causer->email ?? "#{$causer->getKey()}");
    }

    /**
     * Nombre legible de la cuenta afectada.
     */
    private function account(Model $subject): string
    {
        return (string) ($subject->name ?? $subject->email ?? "#{$subject->getKey()}");
    }
}
