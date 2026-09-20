RNF-09 — Confidencialidad de los datos
=====================================

Este documento describe, de forma concreta, qué se cifra en reposo y los
períodos de retención aplicados por el sistema (extraído de la implementación
actual en el repositorio). Está pensado para validación de cumplimiento.


RNF-09.01
Cifrado en reposo
- Qué se cifra: el identificador sensible `dui_nit` se almacena cifrado en su
  columna dedicada. Para búsquedas y validación de unicidad existe un índice
  determinista `dui_nit_hash` que permite operaciones sin descifrar.
- Algoritmo y mecanismo: cifrado con AES-256-CBC usando la infraestructura de
  cifrado de la aplicación (Laravel `Crypt::encryptString`). Índice calculado
  como HMAC-SHA256 usando la `APP_KEY`.
- Normalización: antes de HMAC se normaliza el valor (sin guiones/espacios,
  mayúsculas) para evitar duplicados por formato.
- Implementación (referencias):
  - [app/Support/DuiNitCipher.php](app/Support/DuiNitCipher.php)
  - [app/Models/Candidate.php](app/Models/Candidate.php)


RNF-09.02
Protección de credenciales
- Qué se protege: contraseñas de candidatos (y de administradores en el
  sistema).
- Mecanismo: hashing irreversible usando el driver de hash de Laravel
  (`config/hashing.php`), el campo `password` usa casteo `hashed`.
- Restablecimiento: el restablecimiento de credenciales se realiza vía servicio
  administrativo; la operación queda registrada y el texto en claro no se
  persiste.
- Implementación (referencias):
  - [app/Services/CandidatePasswordResetService.php](app/Services/CandidatePasswordResetService.php)
  - [app/Services/ActivityLogService.php](app/Services/ActivityLogService.php)
  - [app/Models/ActivityLog.php](app/Models/ActivityLog.php)


RNF-09.03
Separación de acceso candidato/administración
- Principio: acceso mínimo — el candidato sólo puede acceder a su propia
  sesión/flujo de test; los resultados y datos psicométricos se exponen sólo
  al panel administrativo mediante autorizaciones por rol.
- Prácticas observadas: atributos sensibles (`dui_nit`, `dui_nit_hash`) no se
  serializan en respuestas; exportaciones y acciones administrativas registran
  eventos de auditoría.


RNF-09.04
Retención y supresión de datos
- Categorías, plazos y acciones (valores por defecto actuales en la
  configuración):
  - `psicometrico`: 1825 días (5 años) — `disociar` (anonimización/desvinculación).
  - `personal`: 1825 días (5 años) — `disociar`.
  - `actividad`: 730 días (2 años) — `suprimir`.
  - `tecnico`: 180 días (6 meses) — `suprimir`.
- Descripción de efectos:
  - `disociar`: elimina identificadores directos (nombre, correo, `DUI/NIT`,
    vínculo con la persona) manteniendo los registros psicométricos desvincu-  
    lados para análisis histórico.
  - `suprimir`: eliminación completa del dato de la categoría.
- Implementación (referencia):
  - [config/retention.php](config/retention.php)


RNF-09.05
Aplicación automática de la retención
- Mecanismo: existe un comando de consola que aplica las políticas
  (`php artisan retention:apply`) y admite `--dry-run` para simulaciones. La
  ejecución respeta el interruptor global `RETENCION_ACTIVA`.
- Auditoría: las operaciones de disociación/supresión quedan registradas para
  trazabilidad en el sistema de logs de actividad.
- Referencias:
  - [config/retention.php](config/retention.php)
  - [app/Services/ActivityLogService.php](app/Services/ActivityLogService.php)


Notas operativas
- Dependencia de claves: el cifrado y el índice HMAC dependen de `APP_KEY`.
  Al rotar `APP_KEY` se debe planear una migración (o diseñar un esquema de
  claves de cifrado separadas) para no perder capacidad de descifrado.
- Backups: la política de retención se aplica sobre la base de datos activa;
  las copias de seguridad siguen un ciclo institucional separado y deben ser
  coordinadas con la política de retención.
- Comando útil (ejemplo de chequeo en entorno de staging):

```bash
php artisan retention:apply --dry-run
```

Registro y trazabilidad
- Las operaciones sensibles (restablecimientos de contraseña, exportaciones,
  aplicación de políticas de retención) se registran en `ActivityLog` y pueden
  revisarse desde el panel administrativo o mediante consultas directas.


¿Qué sigue?
- Si confirmas, puedo:
  - Añadir este archivo al informe principal (`REPORT_RESPUESTA_OBSERVACIONES.md`).
  - Generar un pequeño checklist operativo para el despliegue (rotación de
    `APP_KEY`, pruebas de `retention:apply`, revisión de backups).

-----
Documento generado automáticamente a partir de la implementación actual.
