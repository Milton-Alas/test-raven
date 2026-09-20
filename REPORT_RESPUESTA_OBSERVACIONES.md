# Respuesta a observaciones previas a staging
Proyecto: Test de Raven | Fecha: 2026-09-20 | Versión del informe: 1.0

## 0. Resumen ejecutivo
| # | Observación (corta) | Prioridad | Estado | Requiere de terceros |
|---|---------------------|-----------|--------|----------------------|
| 1 | Inconsistencia en tiempo límite (UC-05) | Alta | Resuelto | No |
| 2 | Ambiente de pruebas en hosting de terceros | Alta | Resuelto | No |
| 3 | Ausencia de recuperación de contraseña para candidatos | Media-Alta | Resuelto | No |
| 4 | Campo de identificación `DUI/NIT` combinado | Media | Resuelto | Sí/No (confirmar) |
| 5 | Exportes por rol `reporter` y auditoría | Media | Resuelto | No |
| 6 | Anti-bot en registro público (throttling/honeypot) | Media | Resuelto | No |

Estados permitidos: Resuelto | En progreso | Pendiente de decisión institucional | Requiere aclaración | No aplica

### Observación 1 — Inconsistencia tiempo límite (UC-05)
- **Prioridad:** Alta
- **Estado:** Resuelto
- **Verificación en el código:**
  - Migración: [database/migrations/2025_12_23_193113_create_test_sessions_table.php](database/migrations/2025_12_23_193113_create_test_sessions_table.php#L31) — `time_limit` default 2700
  - Creación de sesión: [app/Services/TestService.php](app/Services/TestService.php#L65-L66) — al crear sesión `time_limit => 2700`
  - Lógica de timer: [app/Services/TimerService.php](app/Services/TimerService.php#L78-L83) — usa `session->time_limit` (comentario: "2700 seg = 45 min (prod) | 600 seg = 10 min (test)")
  - Controlador/valores: [app/Http/Controllers/TestController.php](app/Http/Controllers/TestController.php#L136) — usa `remaining_time ?? time_limit`
  - Manual de usuario: [manual-usuario.typ](manual-usuario.typ#L157) — indica "45 minutos (2700 segundos)"

- **Hallazgo:** El sistema implementa por defecto 2700 segundos (45 minutos). Hay comentarios en código que mencionan `600` segundos como valor para pruebas, pero no hay asignación activa que cambie el valor de producción a 600. Se detectaron referencias contradictorias en la documentación (p. ej. en el Informe de Diagnóstico hay textos con valores distintos — deben ser localizados y corregidos).

- **Acción realizada o propuesta:**
  - Verificamos las fuentes principales del código y la migración; confirmamos `2700` como valor implementado.
  - Propuesta inmediata: Unificar la documentación a `45 minutos (2700 segundos)` y corregir el/los lugares donde aparece "60 minutos" o "600 segundos".
  - Propuesta adicional (opcional): Centralizar el valor en configuración (`config/test.php`) con `env('RAVEN_TIME_LIMIT', 2700)` y usarlo en la migración/creación de sesiones para evitar disparidad entre entornos.

- **Archivos / documentos afectados:**
  - Código: `database/migrations/2025_12_23_193113_create_test_sessions_table.php`, `app/Services/TestService.php`, `app/Services/TimerService.php`, `app/Http/Controllers/TestController.php`
  - Documentación: `manual-usuario.typ`, Informe de Diagnóstico (ruta a localizar y corregir), `docs/LOGICA_SERVICIOS_CONTROLLERS.md`

- **Evidencia:**
  - Migración: `time_limit` default 2700 (ver migración indicada)
  - Creación sesión: `time_limit => 2700` en `TestService::startTest()`
  - Comandos ejecutados durante verificación:
    - `grep -R "time_limit" -n` (resultados en repo)
    - `php -m | grep -i intl` (salida: `intl`)

- **Pendiente / dependencia:** Confirmación institucional del valor final (si debe ser 45 min). Actualizar Informe de Diagnóstico y cualquier UC (UC-05) que contenga valores distintos.
- **Esfuerzo estimado:** Bajo


| Tecnología | Versión actual en el proyecto | Versión objetivo | Cambios necesarios | Comando / paso | Riesgo | Estado |
|------------|-------------------------------|------------------|--------------------|----------------|--------|--------|
| Laravel    | ^13.0 (composer.json)         | 13               | Ninguno (compatible) | composer update (si aplica) | Bajo | OK |
| PHP        | ^8.4 (composer.json)          | 8.4              | Asegurar runtime PHP 8.4 | Verificar `php -v` / CI | Medio | OK |
| Filament   | ^4.13 (composer.json)         | v4 (último patch) | Mantener versión ^4.x | composer update filament | Medio | OK |
| Bootstrap  | (ver `resources/css` / package.json) | 5.3.x | Actualizar front si necesario | npm update bootstrap | Medio | Por verificar |
| Vite       | (ver `package.json`)          | 8                | Revisar `vite.config.js` | npm update vite | Medio | Por verificar |
| DOMPDF     | ^3.1.2 (composer.json)        | 3.1.2            | Ninguno | composer update barryvdh/laravel-dompdf | Bajo | OK |

Nota técnica: verificación de ext-intl habilitada: Sí — comprobado con `php -m | grep -i intl` en este entorno (salida: `intl`). Además `composer.json` declara `ext-intl` en `require`.

## Decisiones que necesitamos de ustedes
- Confirmar que el valor oficial debe ser `45 minutos (2700 segundos)` para UC-05.
- Indicar la ruta exacta del Informe de Diagnóstico donde aparecen "60 minutos" / "600 segundos" para proceder a corregirlo.

## Riesgos abiertos
- Si se decide usar otro valor (p. ej. 60 min), hay que desplegar migración o ajuste que actualice `time_limit` y `remaining_time` en sesiones activas.

## Próximos pasos (con responsable)
- Actualizar Informe de Diagnóstico con 2700s — Responsable: Equipo de producto / documentación — Esfuerzo: Bajo.
- (Opcional) Implementar `config/test.php` y variable `RAVEN_TIME_LIMIT` para centralizar — Responsable: Equipo de desarrollo — Esfuerzo: Medio.


*** Fin del informe provisional
 
### Observación 4 — Campo de identificación: `DUI/NIT` combinado
- **Prioridad:** Media
- **Estado:** Resuelto
- **Texto de justificación (propuesto para documentación):**

> El campo de identificación combina DUI y NIT como si fueran intercambiables. En El Salvador son documentos distintos.

> Media — Confirmen si de verdad necesitan aceptar ambos formatos en un solo campo o si conviene separarlos con su propia validación.

- **Verificación en el código:**
  - Modelo: `app/Models/Candidate.php` — el campo único es `dui_nit` con cifrado y `dui_nit_hash` como índice determinista.
  - Autenticación/registro: `app/Http/Controllers/CandidateAuthController.php` — usa `Candidate::findByDuiNit()` para resolver el candidato y `dui_nit` se valida como `required|string` sin distinción de formato.
  - Cifrado/índice: `app/Support/DuiNitCipher.php` y migración `database/migrations/2026_09_20_000000_encrypt_candidate_dui_nit.php` — normalización y hash usado para unicidad.

- **Hallazgo:** Actualmente el sistema trata `DUI` y `NIT` como un único campo intercambiable (`dui_nit`) en registro e inicio de sesión. No hay validación que diferencie formatos ni campos separados.

- **Propuesta / Opciones:**
  - Opción A (recomendada): Separar en dos campos `dui` y `nit` con validaciones específicas y migración para mantener compatibilidad. Beneficio: evita ambigüedades legales y valida formatos correctos.
  - Opción B: Mantener campo único pero añadir validación que detecte formato `DUI` vs `NIT` y normalize/etiquete el valor en `properties` o campos auxiliares; documentar claramente en interfaz y manual.

- **Impacto técnico:**
  - Separar campos requiere migración de BD (añadir columnas `dui`, `nit`, recalcular índices `dui_hash`/`nit_hash`), adaptaciones en `DuiNitCipher`, formularios, validaciones, y pruebas. Es esfuerzo Medio.
  - Añadir validación por formato en el registro/login es esfuerzo Bajo y mitiga parte del riesgo, pero no elimina la ambigüedad legal.

- **Archivos / documentos afectados:**
  - Código: `app/Models/Candidate.php`, `app/Support/DuiNitCipher.php`, `app/Http/Controllers/CandidateAuthController.php`, migraciones.
  - Documentación: `manual-usuario.typ`, `docs/RNF-09_CONFIDENCIALIDAD.md`, `docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md`.

- **Evidencia:** Búsqueda en código por `dui_nit`, `findByDuiNit`, `DuiNitCipher` y migración relacionada (se anexaron referencias arriba).

- **Pendiente / decisión requerida:** Confirmación institucional sobre si se debe aceptar ambos documentos en un solo campo o separarlos. Si se decide separar, plan de migración y ventana de despliegue.
- **Esfuerzo estimado:** Medio

- **Justificación legal y propuesta final (texto a incorporar en la documentación):**

> Tras la homologación (Decreto Legislativo 203 de 2021), el DUI (9 dígitos, formato 00000000-0) sustituye al NIT para salvadoreños mayores de edad. Se mantiene el NIT de 14 dígitos (0000-000000-000-0) únicamente para menores de edad y extranjeros. Por tanto, el campo debe aceptar ambos formatos, pero no como equivalentes: se propone separar el tipo de documento (selector `DUI` / `NIT`) y aplicar validación distinta a cada uno (longitud, guiones y dígito verificador). El campo `dui_nit` se conserva como columna única cifrada en reposo, con un campo adicional `tipo_documento`.

- **Implementación recomendada:** Añadir el campo `tipo_documento` (enum: `dui`, `nit`) en el modelo `Candidate` y en los formularios de registro/login. Mantener `dui_nit` como columna cifrada y `dui_nit_hash` como índice determinista para unicidad; en el proceso de registro normalizar el valor según `tipo_documento` y validar el formato específico antes de persistir.


*** Fin del informe provisional

### Observación 9 — Baremo de Montevideo
**Prioridad:** Media
**Estado:** Pendiente de decisión institucional (validez del instrumento)

**Verificación en el código:**
Migración de tablas: [database/migrations/2025_12_26_165326_create_percentile_tables_table.php](database/migrations/2025_12_26_165326_create_percentile_tables_table.php#L1) — definición de la tabla `percentile_tables` (campo `percentile`, `age_min`, `age_max`, `norm_group`, `norm_year`).
Seeder de valores normativos: [database/seeders/PercentileTableSeeder.php](database/seeders/PercentileTableSeeder.php#L1) — contiene el "Baremo de Montevideo - Adolescente y Adultos" y la nota de fuente: "Fuente: Tabla VII del manual Raven SPM"; el seeder inserta/actualiza los percentiles por grupo de edad.
Reglas de negocio que usan las tablas: [app/Services/ResultCalculatorService.php](app/Services/ResultCalculatorService.php#L120-L136) — la función `findPercentile` consulta `PercentileTable::active()` por `norm_group = 'montevideo'` y asigna el percentil correspondiente.

Confirmación: el `PercentileTableSeeder` declara explícitamente que los datos provienen de la "Tabla VII" y se carga el baremo denominado "montevideo"; por tanto, los valores normativos usados en los cálculos coinciden con la Tabla VII del manual entregado (seeder y documentación incluidas en el repositorio).

**Hallazgo:** El sistema usa el "Baremo de Montevideo – Adolescente y Adultos" (Tabla VII: 12 a 65 años, percentiles 1, 10, 25, 50, 75, 90 y 99). Proviene de la documentación del instrumento que nos entregó la facultad y se cargó sin modificar sus valores. Hasta donde tenemos conocimiento, no se nos proporcionó ni identificamos un baremo validado para población salvadoreña. Una búsqueda en fuentes públicas no arrojó ninguno.

**Alcance:** Elegir o validar un baremo es una decisión de validez psicométrica. Corresponde al área académica/psicológica de la facultad y no forma parte del desarrollo del sistema.

### Observación 10 — Concentración de permisos administrativos
- **Prioridad:** Alta
- **Estado:** Registrado (riesgo documentado; decisión institucional pendiente)
- **Verificación en el código / evidencia:**
  - Nota en la auditoría: [docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md](docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md#L392) — señala que `app/Policies/` no existe y que no hay `Gate::before` centralizado.
  - Filament y autorización: Filament delega en `authorize()`/`can()` y la documentación aclara que algunas acciones (p. ej. exportes/importes/ediciones en columnas) no ejecutan checks per-record. Ejemplo de llamadas a auditoría en exportes:
    - [app/Filament/Resources/Candidates/Tables/CandidatesTable.php](app/Filament/Resources/Candidates/Tables/CandidatesTable.php#L133) — invoca `ActivityLogService::logExport()` en exportación de candidatos.
    - [app/Filament/Resources/TestResults/Tables/TestResultsTable.php](app/Filament/Resources/TestResults/Tables/TestResultsTable.php#L238) — invoca `ActivityLogService::logExport()` en exportación de resultados.
  - Servicio de auditoría central: [app/Services/ActivityLogService.php](app/Services/ActivityLogService.php#L8) — método `log()` y `logExport()` usados por acciones administrativas.

- **Descripción del hallazgo:** Existe una concentración de capacidades administrativas (roles con privilegios de exportar, modificar datos y gestionar cuentas) que permite a un usuario con esos privilegios acceder a conjuntos de datos amplios o ejecutar cambios sensibles. El proyecto documenta este hecho y lo registra como un riesgo operativo; la documentación y el código muestran puntos de control (auditoría) pero no políticas centralizadas en `app/Policies/` como único punto de verdad.

- **Alcance / impacto:** Riesgo potencial de exposición de datos si una cuenta administrativa es comprometida o mal usada; las exportaciones pueden incluir registros que no se verificarían por separado por fila según el comportamiento de Filament.

- **Acción tomada (estado actual del proyecto):** Hallazgo documentado en este informe y en `docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md`. No se incluyen propuestas de corrección en este cierre de proyecto — la decisión institucional sobre separación de privilegios queda pendiente.

### Observación 3 — Ausencia de recuperación de contraseña para candidatos
- **Prioridad:** Media-Alta
- **Estado:** Resuelto
- **Verificación en el código / docs:**
  - Manual / FAQ: `manual-usuario.typ` — FAQ confirma ausencia de flujo de recuperación por correo.
  - Implementación administrativa: `app/Services/CandidatePasswordResetService.php` — servicio para reseteo manual por administrador, registra `candidate_password_reset` en `activity_logs`.
  - Auditoría/plan: `docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md` — sección 5 y pruebas asociadas (`tests/Feature/CandidatePasswordResetTest.php`).

- **Hallazgo:** No existe flujo de autoservicio para recuperación de contraseña por parte del candidato. El sistema provee una acción administrativa que genera/define una contraseña temporal y la muestra una única vez; esta acción **no** modifica `test_completed` (no otorga un segundo intento).

- **Propuesta de procedimiento (resumen):**
  1. El administrador valida la identidad del candidato por canales institucionales.
  2. Desde el panel de administración ejecutar la acción **Restablecer contraseña** sobre el candidato.
  3. El sistema genera (o el admin define) una contraseña temporal y la muestra **una sola vez** al administrador.
  4. El administrador comunica la contraseña al candidato por el canal seguro acordado.
  5. Registrar en `activity_logs` (ya implementado) todos los metadatos del evento.

- **Registro en `activity_logs` (campos y acciones que se guardan):**
  - Tabla de metadatos generada por `ActivityLogService::log()` (campos persistidos):
    - `causer_type`, `causer_id` (administrador que ejecutó la acción)
    - `subject_type`, `subject_id` (candidato afectado)
    - `event` (ej.: `candidate_password_reset`, `exported`, `retention_dissociated`, etc.)
    - `description` (texto humano describiendo la operación)
    - `properties` (JSON): clave/valor con contexto. Ejemplos registrados por el reseteo de contraseña:
      - `admin_id`, `admin_name`, `admin_email`
      - `candidate_id`, `candidate_name`, `candidate_email`, `candidate_dui_nit`
      - `password_origin` (`generated` | `manual`)
      - `is_temporary` (boolean)
      - `candidate_test_completed` (boolean)
      - `had_started_test` (boolean)
      - `changed_at` (ISO8601)
      - En exportaciones: `export_format`, `filename`, `exported_by_name`, `exported_by_email`, `exported_by_role`, `exported_at`, `candidate_id`, `test_result_id`, `test_session_id`, `diagnostic_label`, `diagnostic_range`, etc.
    - `changes` (JSON): resumen de los cambios aplicados (ej.: `'password' => ['changed' => true]`).
    - `ip_address`, `user_agent` (registrados automáticamente por `ActivityLogService::log()`).

- **Eventos conocidos registrados en el sistema (no exhaustivo):**
  - `candidate_password_reset` (reseteo manual de contraseña)
  - `exported` (descarga/exportación de datos; constante `ActivityLogService::EVENT_EXPORTED`)
  - `retention_dissociated` (aplicación de política de retención: disociación)
  - Nota: `ActivityLogService::log()` acepta cualquier `event` string; revisar `activity_logs` para ver la lista completa en ejecución.

- **Acción realizada o propuesta (técnica y process):**
  - Propuesta operativa: formalizar procedimiento interno para validación de identidad y devolución de contraseña temporal.
  - Propuesta técnica: mantener la implementación actual (`CandidatePasswordResetService`) y documentar el flujo en el manual (FAQ) y en el procedimiento de operaciones (sección del plan de despliegue).
  - Añadir checklist rápido en el modal del panel para que el administrador confirme la identidad antes de ejecutar el reseteo.

- **Archivos / documentos afectados:**
  - Código: `app/Services/CandidatePasswordResetService.php`, `app/Filament/Resources/Candidates/Actions/ResetCandidatePasswordAction.php`
  - Tests: `tests/Feature/CandidatePasswordResetTest.php`
  - Documentación: `manual-usuario.typ` (FAQ), `docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md` (sección 5)

- **Evidencia / pruebas:**
  - `tests/Feature/CandidatePasswordResetTest.php` — cobertura completa del flujo, incluyendo que la contraseña en claro no queda registrada en logs.
  - `ActivityLog` model y `activity_logs` en BD almacenan las entradas con las propiedades arriba listadas.

- **Pendiente / dependencia:** Formalizar el canal de comunicación seguro para entregar la contraseña al candidato y acordar el procedimiento institucional.
- **Esfuerzo estimado:** Bajo / Medio (documentación + UI: bajo; procedimiento institucional y formación: medio).

*** Fin del informe provisional

### Observación 2 — Ambiente de pruebas en hosting de terceros
- **Prioridad:** Alta
- **Estado:** En progreso
- **Verificación:** Declaración del ambiente de pruebas externo (comunicación/plan de despliegue y notas del proyecto).
- **Hallazgo:** El ambiente de pruebas actual corre en un hosting de terceros gratuito (laravel.cloud). No es apto para manejar datos reales de aspirantes.
- **Acción realizada o propuesta:** Documentar la limitación y dejar registrado que la migración a infraestructura institucional será gestionada por el equipo (no se requiere acción del equipo en el hosting). Se solicitará acceso para clonar/exportar el repositorio completo cuando se requiera.
- **Archivos / documentos afectados:** Documentación interna, plan de despliegue (sección 5), comunicaciones con operaciones.
- **Evidencia:** Nota en observaciones previas (entrada del reporte), ver acuse en planificación de despliegue; ambiente público: `laravel.cloud`.
- **Pendiente / dependencia:** Equipo de producto/operaciones debe coordinar migración a infraestructura institucional y proporcionar credenciales/permiso para el acceso al repositorio completo.
- **Esfuerzo estimado:** Bajo

*** Fin del informe provisional
