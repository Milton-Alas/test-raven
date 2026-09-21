# Test de Matrices Progresivas de Raven — UES

Aplicación web para administrar la **Escala General del Test de Matrices Progresivas de Raven**:
registro de candidatos, aplicación del test con control de tiempo, calificación automática
(puntaje por serie, percentil y rango diagnóstico), panel administrativo con reportes, y
cumplimiento de las obligaciones de confidencialidad y retención de datos.

Desarrollado para la Universidad de El Salvador (UES).

---

## Índice

- [Qué hace el sistema](#qué-hace-el-sistema)
- [Stack](#stack)
- [Arquitectura](#arquitectura)
- [Flujo del candidato](#flujo-del-candidato)
- [Panel administrativo](#panel-administrativo)
- [Calificación](#calificación)
- [Confidencialidad y retención de datos](#confidencialidad-y-retención-de-datos)
- [Instalación](#instalación)
- [Entornos y variables](#entornos-y-variables)
- [Respaldo y operación](#respaldo-y-operación)
- [Pruebas](#pruebas)
- [Estructura del repositorio](#estructura-del-repositorio)

---

## Qué hace el sistema

El test consta de **60 reactivos** organizados en **5 series (A–E) de 12 ejercicios** cada una. El
candidato los responde en orden, sin poder retroceder, con un límite de **45 minutos**. Al finalizar,
el sistema calcula:

- Puntaje por serie y puntaje total (0–60).
- **Percentil** según la edad del candidato, a partir de tablas normativas.
- **Rango diagnóstico** (I a V) con su interpretación.
- **Validez del protocolo**, comparando el patrón de respuestas por serie contra el esperado
  (criterio ±2 por serie).

Los resultados se consultan y exportan desde el panel administrativo, y cada candidato puede
descargar su informe.

---

## Stack

| Componente | Versión | Nota |
| --- | --- | --- |
| PHP | **8.4** | requiere `ext-intl`, `ext-gd` y `ext-zip` |
| Laravel | **13** | |
| Filament | **4** | panel administrativo |
| Livewire | 4 | |
| MySQL | 8 | |
| Vite | 8 | |
| Tailwind CSS | 4 | |
| DomPDF | 3 | informes en PDF |
| PHPUnit | 11 | 146 pruebas |

**Extensiones de PHP necesarias:** `intl` (Filament usa `Number::format`), `gd` (DomPDF incrusta el
logo del informe) y `zip` (exportaciones a Excel vía `openspout`). Están declaradas en
`composer.json`, así que `composer install` avisa si falta alguna en lugar de fallar en ejecución.

---

## Arquitectura

### Capas

```
app/
├── Console/Commands/     retention:apply · app:backup-encryption-key
├── Exceptions/           HistoricalDataException (protección del instrumento)
├── Filament/             Panel administrativo
│   ├── Resources/        8 recursos (ver abajo)
│   ├── Widgets/          5 widgets del tablero
│   └── Support/          HistoricalContent (reglas de interfaz del instrumento)
├── Http/
│   ├── Controllers/      CandidateAuth · Test · TestInstructions · TestResult
│   └── Middleware/       CandidateAuth · EnsureTestNotCompleted · RedirectIfAuthenticated
├── Models/               13 modelos
│   └── Concerns/         PreservesHistoricalData
├── Services/             Lógica de negocio (ver abajo)
└── Support/              DuiNitCipher (cifrado e índice del identificador)
```

### Servicios: dónde vive la lógica de negocio

Los controladores son delgados; las reglas viven en servicios, que es lo que permite probarlas de
forma aislada.

| Servicio | Responsabilidad |
| --- | --- |
| `TestService` | Inicio, reanudación y finalización del test; guardado de respuestas; progreso |
| `TimerService` | Control de tiempo autoritativo desde el servidor (`started_at`), penalizaciones y datos del cronómetro |
| `ResultCalculatorService` | Cálculo de puntajes, percentil, rango diagnóstico y validez por discrepancia |
| `CandidatePasswordResetService` | Reseteo manual de credenciales por un administrador, con auditoría |
| `RetentionService` | Aplicación de las políticas de retención (disociar / suprimir) |
| `ActivityLogService` | Registro de auditoría de acciones: reseteo de credenciales, exportaciones, altas y cambios de rol de cuentas del panel, y disociación por retención |

### Modelo de datos

```
Candidate ──1:1── TestSession ──1:N── TestAnswer ──N:1── TestQuestion ──N:1── TestSeries
    │                    │                                                        │
    │                    └──1:1── TestResult                                  AnswerOption
    │
    └──1:N── ActivityLog (polimórfico: causer / subject)

Tablas normativas:  PercentileTable (percentil por edad) · DiagnosticRange (rangos I–V)
                    DiscrepancyPattern (patrón esperado por puntaje total)
Operación:          retention_logs (evidencia de la retención aplicada)
```

**Puntos de integridad que conviene conocer:**

- **Una sola sesión por candidato** (`unique(candidate_id)` en `test_sessions`) y **una sola
  respuesta por pregunta** (`unique(test_session_id, test_question_id)`).
- **Un resultado por sesión** (`unique(test_session_id)`).
- Las **relaciones tienen borrado en cascada** desde la sesión: eliminar una sesión arrastra sus
  respuestas y su resultado. Por eso las sesiones son de solo lectura en el panel.
- El **identificador del candidato** (`dui_nit`) está cifrado, con un índice HMAC para búsqueda
  (ver [Confidencialidad](#confidencialidad-y-retención-de-datos)).

---

## Flujo del candidato

```
/login  →  /instrucciones  →  POST /test/start  →  GET /test/question
                                                          ↓
                          POST /test/answer ──────────────┘  (AJAX: guarda y avanza un ítem)
                                    ↓
                          /test/completed   (resultado + informe PDF)
```

**Control de tiempo:** el cronómetro se muestra con un Web Worker en el navegador, pero **el tiempo
lo manda el servidor**: `TimerService` recalcula el restante a partir de `started_at` y nunca permite
que aumente. Lo que el cliente reporte solo puede reducirlo, jamás ampliarlo.

**Una sola oportunidad:** `Candidate::test_completed` bloquea un segundo intento. Si el candidato
olvida su contraseña, no hay autoservicio: un administrador la restablece desde el panel (ver
[Panel administrativo](#panel-administrativo)).

**Límites de intentos:** el registro público está limitado a 5 registros por hora e IP y 3 por hora
sobre el mismo DUI/NIT; el login, a 5 intentos fallidos por minuto e IP y 5 por cada 15 minutos sobre
el mismo identificador. Los intentos correctos no consumen cuota.

---

## Panel administrativo

Disponible en `/admin`, con autenticación propia (guard `web`) y autorización por roles.

| Rol | Alcance |
| --- | --- |
| `admin` | Todo: consulta, operación diaria (candidatos y resultados), usuarios y exportaciones |
| `reporter` | Solo consulta de candidatos, sesiones y resultados, con exportación auditada |
| `evaluador` | Solo consulta de las evaluaciones y del instrumento, más el informe PDF individual |

El instrumento (reactivos, series, baremos y rangos diagnósticos) es **de solo lectura para los tres
roles**, `admin` incluido: no es configuración operativa, es el instrumento normalizado con el que se
calcularon los resultados ya emitidos, y cualquier cambio real entra por seeder o migración, con
control de versiones y rastro.

### Recursos

| Recurso | Para qué sirve | Reporter | Evaluador |
| --- | --- | --- | --- |
| **Candidatos** | Consulta, reseteo de contraseña (solo admin) y exportación CSV | consulta + exportación | consulta |
| **Sesiones** | Seguimiento del proceso (solo lectura, incluido el admin) | consulta | consulta |
| **Resultados** | Puntajes, percentil, diagnóstico, informe PDF y exportación a Excel/CSV | consulta + exportación | consulta + informe PDF |
| **Reactivos** | Consulta del banco de 60 preguntas (solo lectura) | sin acceso | consulta |
| **Series** | Consulta de las 5 series (solo lectura) | sin acceso | consulta |
| **Baremos** | Tablas de percentil por edad (solo lectura) | sin acceso | consulta |
| **Rangos diagnósticos** | Interpretación de los rangos I–V (solo lectura) | sin acceso | consulta |
| **Usuarios** | Alta y roles del personal del panel (`admin`, `reporter`, `evaluador`) | sin acceso | sin acceso |

Cada recurso responde a la pregunta *«¿puede este rol hacer esto?»* con sus propios métodos
`canViewAny` / `canCreate` / `canEdit` / `canDelete` / `canForceDeleteAny` / `canRestoreAny`. No hay
policies registradas, así que **esos métodos son la única fuente de verdad** de la autorización.
Las exportaciones masivas tienen su propio método (`canExport()` en Candidatos y Resultados): el rol
`evaluador` no las tiene.

### Tablero

Cinco widgets: resumen de indicadores, tendencia de tests por día, distribución por rango
diagnóstico, gráfico de resultados y últimas sesiones.

### El instrumento es de solo lectura

Las series, reactivos, baremos y rangos diagnósticos **no se pueden crear, editar ni borrar desde el
panel**, para ningún rol: no hay botón de alta, ni acción de edición, ni de borrado, y las páginas de
`create` y `edit` responden 403. La razón es doble:

> El contenido del instrumento determina el puntaje, el percentil y el diagnóstico de tests ya
> rendidos. Un cambio hecho a mano desde la interfaz dejaría resultados anteriores calculados con otra
> norma, sin rastro de quién lo cambió ni por qué.
>
> Además, `test_answers.test_question_id` tiene **borrado en cascada**. Eliminar un reactivo borraría
> las respuestas de todos los candidatos que lo respondieron, y un recálculo posterior daría un puntaje
> sobre menos ítems (por ejemplo 48 en vez de 60) sin que nadie lo note.

Los cambios reales se hacen por **seeder o migración**, con control de versiones en el historial del
repositorio. La protección está en tres capas: la autorización de cada Resource (`canCreate()` y
`canEdit()` devuelven `false`), la interfaz (sin acciones que las invoquen) y los modelos
(`PreservesHistoricalData`), que además cubren `artisan tinker` y cualquier código futuro.

---

## Calificación

`ResultCalculatorService` concentra el cálculo y se ejecuta al finalizar la sesión:

1. **Puntajes por serie** contando respuestas correctas (comparando contra `test_questions.correct_answer`).
2. **Percentil** según la edad y el puntaje total, buscando en `percentile_tables` el registro exacto
   o el inmediato inferior.
3. **Rango diagnóstico** desde `diagnostic_ranges` según el percentil obtenido.
4. **Validez** comparando el patrón por serie contra `discrepancy_patterns`: si alguna serie difiere
   más de ±2 del esperado, el protocolo se marca como inválido con la explicación.
5. **Tiempos**: total empleado y promedio por ítem.

El cálculo es **idempotente**: usa `updateOrCreate` sobre `test_results`, y como `result.candidate_id`
apunta al candidato, disociar a un candidato conserva el puntaje pero elimina la atribución personal.

---

## Confidencialidad y retención de datos

Cumple los requisitos **RNF-09** de la Ley de Protección de Datos Personales de El Salvador
(D.L. 144/2024). El detalle de cumplimiento, con el mapeo de cada requisito a su código y su prueba,
está en [`docs/RNF-09_CONFIDENCIALIDAD.md`](docs/RNF-09_CONFIDENCIALIDAD.md).

**Resumen:**

- **Cifrado en reposo:** el `dui_nit` se guarda cifrado con AES-256-CBC y se busca por un índice
  HMAC-SHA256 (`dui_nit_hash`) con restricción única, sin necesidad de descifrar.
- **Credenciales:** bcrypt irreversible; el restablecimiento lo hace un administrador y queda auditado.
- **Separación de acceso:** guards independientes para candidato y administración, con autorización
  por roles. Los datos psicométricos no están al alcance del candidato.
- **Auditoría (`activity_logs`):** cinco eventos —`candidate_password_reset`, `exported`,
  `retention_dissociated`, `user_created` y `user_role_changed`— con causer, subject, `properties`
  (JSON), `changes` (JSON), IP y user agent. El rol de una cuenta se audita pase por donde pase (panel,
  consola o `tinker`), porque lo escriben los eventos del modelo `User`. Las contraseñas —ni su hash—
  nunca entran en el registro.
- **Retención configurable:** cuatro categorías (personal, psicométrico, actividad, técnico) con
  plazo y acción (`disociar` / `suprimir`) ajustables por entorno.
- **Aplicación automática:** tarea diaria que deja registro en `retention_logs`.

### Cifrado en reposo del identificador

El `dui_nit` se guarda cifrado con **AES-256-CBC** (el cifrado de la aplicación, vía
`Crypt::encryptString`, en `app/Support/DuiNitCipher.php`). La búsqueda y la validación de unicidad no
descifran nada: se resuelven contra `dui_nit_hash`, un **HMAC-SHA256** calculado con `APP_KEY` sobre el
valor normalizado (sin guiones ni espacios, en mayúsculas) y protegido por el índice único
`candidates_dui_nit_hash_unique`.

Son dos representaciones por una razón concreta: el cifrado usa un IV aleatorio —el mismo DUI produce
un texto cifrado distinto cada vez, lo que es correcto para confidencialidad pero impide buscar por
igualdad— y el HMAC es determinista, así que permite buscar, validar duplicados y agrupar. Se usa HMAC
y no un hash simple porque un DUI salvadoreño tiene del orden de 10⁸ combinaciones válidas: un SHA-256
sin clave se revierte con una tabla precalculada y anularía el cifrado.

Consecuencias prácticas: `dui_nit_hash` está en `$hidden`, así que no se serializa en ninguna respuesta;
el `APP_KEY` es imprescindible, porque sin él no se puede descifrar ni recalcular el índice (ver la
advertencia al final de esta sección); y donde el identificador se muestra, se muestra **completo**: el
listado de candidatos del panel, la exportación CSV y el informe PDF lo leen descifrado, porque van
dirigidos a los roles autorizados y la exportación queda auditada. El accessor `dui_nit_masked`
(`•••••4567`) existe precisamente para enmascararlo en listados y exportaciones, pero hoy ninguna de
esas tres vistas lo usa.

### Separación de acceso entre candidato y administración

Son dos guards distintos en `config/auth.php`: `web` para el personal del panel y **`candidate`** para
los candidatos. El candidato solo alcanza las rutas de su propio flujo (`/instrucciones` y `/test/*`,
con el middleware `auth:candidate`), y cada controlador resuelve su sesión desde
`Auth::guard('candidate')->user()`: **ninguna ruta acepta el identificador de una sesión ajena**, así
que no puede leer ni escribir datos de otro candidato.

Los datos psicométricos viven únicamente en el panel: no hay ninguna ruta del candidato que devuelva el
resultado del test. La pantalla de finalización confirma que el test se completó, pero no muestra
puntaje, percentil ni clasificación diagnóstica. Del lado del panel, el acceso a esos datos se resuelve
con la autorización por roles descrita más arriba.

### Retención y supresión de datos

Cuatro categorías en `config/retention.php`, cada una con su plazo y su acción configurables por
variable de entorno (se ajustan en el `.env` sin tocar código):

| Categoría | Qué alcanza | Variable de plazo | Acción |
| --- | --- | --- | --- |
| `personal` | Datos identificativos: nombre, correo, DUI/NIT, edad, ocupación y nivel educativo | `RETENCION_PERSONAL_DIAS` | `disociar` (`RETENCION_PERSONAL_ACCION`) |
| `psicometrico` | Expediente psicométrico: resultados, puntajes, percentiles, diagnósticos y respuestas | `RETENCION_PSICOMETRICO_DIAS` | `disociar` (`RETENCION_PSICOMETRICO_ACCION`) |
| `actividad` | Logs de auditoría (`activity_logs`) | `RETENCION_ACTIVIDAD_DIAS` | `suprimir` (`RETENCION_ACTIVIDAD_ACCION`) |
| `tecnico` | Trazas técnicas: IP, user agent y datos del navegador | `RETENCION_TECNICO_DIAS` | `suprimir` (`RETENCION_TECNICO_ACCION`) |

**Disociar** elimina los identificadores directos (nombre, correo, DUI/NIT e índice, edad, ocupación y
nivel educativo) y conserva el dato desagregado, que deja de ser atribuible a una persona; los datos
psicométricos se disocian —no se suprimen— para no romper la serie histórica con la que se calibra el
baremo. **Suprimir** elimina el dato por completo. El interruptor general es `RETENCION_ACTIVA`.

La aplicación es `php artisan retention:apply`, también programada a diario por el planificador
(`--dry-run` permite revisar el alcance sin modificar nada y `--categoria=` limita la ejecución a una
sola). Cada ejecución queda como evidencia en `retention_logs`. Quedan fuera de la política el banco de
ítems, las cuentas del panel y las copias de seguridad.

> **Advertencia operativa:** el `APP_KEY` cifra los identificadores. Si se pierde, los datos cifrados
> son irrecuperables. Debe respaldarse junto con la base — ver
> [`docs/RESPALDO_Y_OPERACION.md`](docs/RESPALDO_Y_OPERACION.md).

---

## Instalación

Requiere **PHP 8.4** con `intl`, `gd` y `zip`, **Composer 2**, **Node 20+** y **MySQL 8**.

```bash
# 1. Dependencias
composer install
npm install

# 2. Entorno
cp .env.example .env
php artisan key:generate        # genera la APP_KEY (¡respáldala!)

# 3. Base de datos (configura las credenciales en .env antes)
php artisan migrate --force

# 4. Banco de reactivos y tablas normativas
php artisan db:seed --force

# 5. Assets y enlace de almacenamiento
npm run build
php artisan storage:link

# 6. Servidor de desarrollo
php artisan serve
```

El seeder crea el banco de 60 reactivos, las tablas de percentiles, los rangos diagnósticos y los
patrones de discrepancia, más dos usuarios de prueba. **En producción, cambia sus contraseñas o crea
usuarios propios antes de exponer el panel.**

### Con `composer`

```bash
composer setup    # install + .env + key + migrate + npm install + npm run build
composer dev      # servidor + cola + logs + vite en paralelo
composer test     # suite de pruebas
```

---

## Entornos y variables

`.env.example` documenta **todas** las variables que la aplicación espera, con notas sobre qué cambia
en producción. Las imprescindibles:

```env
APP_ENV=production          # local en desarrollo
APP_DEBUG=false             # true expone el entorno en cada error
APP_KEY=base64:...          # cifra los DNI/NIT: respáldala
APP_URL=https://...

DB_CONNECTION=mysql
DB_DATABASE=test_raven
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_ENCRYPT=true        # recomendado en producción
CACHE_STORE=database        # el contador de límites de intentos vive aquí
QUEUE_CONNECTION=database
MAIL_MAILER=smtp            # con "log" los correos solo quedan en el registro
```

**Sesiones y caché:** con `SESSION_DRIVER=array` o `CACHE_STORE=array` los límites de intentos no se
comparten entre procesos, así que dejarían de ser efectivos. Usa un store persistente.

**Pruebas:** `.env.testing` aísla la suite del `.env` de desarrollo. `tests/TestCase.php` aborta si la
conexión no es SQLite, porque `RefreshDatabase` borraría la base configurada. Ver
[Pruebas](#pruebas).

---

## Respaldo y operación

Guía completa en [`docs/RESPALDO_Y_OPERACION.md`](docs/RESPALDO_Y_OPERACION.md). Lo esencial:

```bash
# Respaldo completo (base + clave de cifrado) a un volumen externo
bash bin/backup.sh /mnt/respaldos

# Ver qué haría la retención, sin modificar nada
php artisan retention:apply --dry-run

# Aplicar la retención
php artisan retention:apply
```

**Cron mínimo en el servidor** — la primera línea es obligatoria (activa la retención automática):

```cron
* * * * * www-data cd /var/www/test-raven && php artisan schedule:run >> /dev/null 2>&1
30 2 * * * www-data cd /var/www/test-raven && bash bin/backup.sh /mnt/respaldos >> storage/logs/backup.log 2>&1
```

---

## Pruebas

```bash
php artisan test                      # suite completa
php artisan test --filter=Rnf09       # confidencialidad y retención
php artisan test --filter=TestBank    # integridad del instrumento
```

**146 pruebas, 667 aserciones.** Organizadas por lo que protegen:

| Archivo | Qué garantiza |
| --- | --- |
| `Rnf0901DuiNitEncryptionTest` | El DUI/NIT no se guarda en claro; el índice es HMAC, no un hash simple; la búsqueda no descifra |
| `Rnf0903AccessSeparationTest` | Candidato y administración no se cruzan; los datos psicométricos requieren rol |
| `Rnf09RetentionTest` | La retención disocia/suprime solo lo vencido, y cada operación queda registrada |
| `TestBankIntegrityTest` | No se puede borrar ni reescribir el contenido del test |
| `TestBankUiRulesTest` | La interfaz del instrumento es de solo lectura: sin alta, edición ni borrado |
| `PanelRolePermissionsTest` | Matriz de permisos de `admin`, `reporter` y `evaluador` sobre los ocho recursos |
| `UserAccountAuditTest` | El alta y el cambio de rol de una cuenta del panel quedan auditados, sin credenciales |
| `TestAssetsIntegrityTest` | Las láminas que la base referencia existen en el repositorio |
| `CandidateRegistrationThrottleTest` / `CandidateLoginThrottleTest` | Límites de intentos en registro y login |
| `CandidatePasswordResetTest` | Reseteo por administrador con auditoría y sin filtrar credenciales |
| `TestResultPdfExportTest` | Informe PDF y auditoría de exportaciones |
| `CandidateCsvExportTest` | Las cabeceras del CSV cuadran con los datos |
| `PanelDestructiveActionsTest` | El rol reporter no puede ejecutar acciones destructivas |
| `AdminPanelSmokeTest` / `TestDatabaseSafeguardTest` | Las páginas del panel responden; la suite no toca datos reales |

### Dos salvaguardas que conviene conocer

1. **`tests/TestCase.php` aborta si la conexión no es SQLite.** `RefreshDatabase` ejecuta
   `migrate:fresh`, que elimina todas las tablas de la conexión configurada. La comprobación convierte
   un accidente destructivo e irreversible en un error inmediato.
2. **`.env.testing` aísla la suite.** Sin él, el `.env` de desarrollo se cargaba después de que PHPUnit
   aplicara su configuración y `composer test` terminaba migrando la base de desarrollo.

### Estado actual

145 pruebas en verde y **1 en rojo**: `Tests\Feature\ExampleTest`, un ejemplo del esqueleto de Laravel
que espera un 200 en `/` y recibe la redirección al login. No cubre funcionalidad del sistema.

---

## Estructura del repositorio

```
├── app/                    Código de la aplicación
├── bin/
│   ├── backup.sh           Respaldo de base + clave de cifrado
│   └── php                 Lanzador con un PHP que incluye intl/gd/zip (solo si el del sistema no los tiene)
├── config/                 Configuración, incluida retention.php
├── database/
│   ├── migrations/         Esquema, incluido el cifrado del DNI/NIT
│   └── seeders/            Banco de reactivos y tablas normativas
├── docs/
│   ├── RNF-09_CONFIDENCIALIDAD.md          Cumplimiento de confidencialidad y retención
│   ├── RESPALDO_Y_OPERACION.md             Comandos, respaldo y cron
│   ├── AUDITORIA_Y_PLAN_ACTUALIZACION.md   Auditoría técnica y pendientes
│   ├── LOGICA_SERVICIOS_CONTROLLERS.md     Diseño de servicios y controladores
│   └── PLAN_PANEL_ADMIN_FILAMENT.md        Diseño del panel
├── resources/
│   ├── css/                Tailwind 4 (configuración en CSS, con los colores UES)
│   ├── js/                 Alpine
│   └── views/              Vistas del candidato, autenticación e informes
├── routes/                 Rutas web y programación de tareas
├── storage/app/public/     Láminas de los reactivos (versionadas) y subidas
└── tests/                  Suite de pruebas
```

---

## Licencia

MIT, según el esqueleto de Laravel del que parte el proyecto.
