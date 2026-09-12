# Auditoría técnica y estado del stack (Test de Matrices Progresivas de Raven)

**Fecha:** 12 de septiembre de 2026
**Rama de trabajo:** `feat/stack-upgrade-and-new-features`
**Alcance:** auditoría de solo lectura sobre `main` (commit `33d2fb2`) + actualización de stack aplicada en esta rama.

Este documento resume **qué se verificó en el código** y **qué fallos quedaron pendientes**.
Cada hallazgo incluye `archivo:línea` como evidencia. Lo que no pudo comprobarse por código se marca explícitamente.

> **Importante:** los fallos de la sección 4 **no se corrigieron** en esta rama. La actualización de stack
> (sección 2) se hizo de forma que no dependiera de ellos ni los empeorara.

---

## 1. Estado actual y objetivo

| Comprobación | Antes | Después de esta rama |
| --- | --- | --- |
| PHP | `^8.2` en `composer.json`, 8.3.33 en la máquina | `^8.4` (verificado con 8.4.23) |
| Laravel | 12.44.0 | **13.31.0** |
| Filament | 4.8.0 | **4.13.1** (se mantiene la major 4, sin salto a v5) |
| Tailwind | v3.4.19 con `postcss.config.js` + `tailwind.config.js` | **v4.3.3** vía `@tailwindcss/vite` |
| Vite | 7.3.0 (`laravel-vite-plugin` 2.x) | **8.3.0** (`laravel-vite-plugin` 3.2.0) |
| Livewire | 3.7.11 | 3.8.8 |
| PHPUnit | 11.5.46 | 11.5.56 |
| Advisories de seguridad | 64 en 21 paquetes (13 de severidad *high*) | por medir tras la actualización |

**Funcionalidad de negocio:** un candidato se registra, realiza un test Raven de 60 ítems (series A–E,
12 por serie) con límite de 45 minutos, y el sistema calcula puntaje por serie, percentil, rango
diagnóstico y validez por discrepancia (±2 por serie).

### Arquitectura de la aplicación

```
app/
├── Filament/         Panel admin (8 Resource, 5 Widget)  -> /admin
├── Http/Controllers/ CandidateAuth, Test, TestInstructions, TestResult
├── Http/Middleware/  CandidateAuth, CheckPanelAccess, EnsureTestNotCompleted, RedirectIfAuthenticated
├── Models/           Candidate, TestSession, TestSeries, TestQuestion, AnswerOption,
│                     TestAnswer, TestResult, PercentileTable, DiagnosticRange,
│                     DiscrepancyPattern, ActivityLog, User
└── Services/         TestService, TimerService, ResultCalculatorService, ActivityLogService
```

Flujo del candidato: `login` → `/instrucciones` → `POST /test/start` → `GET /test/question`
→ `POST /test/answer` (AJAX, avanza ítem) → `/test/completed`. El cronómetro corre en un Web Worker
(`public/js/timer-worker.js`) y el servidor recalcula el tiempo desde `started_at`
(`app/Services/TimerService.php:94-105`), por lo que **el cliente no puede ganar tiempo**.

---

## 2. Actualización de stack aplicada

### 2.1 Backend

`composer.json`:

- `php`: `^8.2` → **`^8.4`**
- `laravel/framework`: `^12.0` → **`^13.0`**
- `filament/filament`: `^4.0` → **`^4.13`** (exige `ext-intl`, ya presente)
- `laravel/tinker`: `^2.10.1` → **`^3.0`** — *necesario*: tinker 2.x limita
  `illuminate/support` a `^12.0` y bloquea la resolución con Laravel 13
- Dev: `laravel/pail ^1.2.7`, `laravel/pint ^1.32`, `laravel/sail ^1.67`,
  `mockery/mockery ^1.6.15`, `nunomaduro/collision ^8.9`, `phpunit/phpunit ^11.5.50`

`composer.json:53-57` — el hook `post-autoload-dump` ejecutaba `@php artisan filament:upgrade`,
comando deprecado en Filament 4; se sustituyó por `@php artisan filament:assets`.

> **Nota de operación:** el hook `Illuminate\Foundation\ComposerScripts::prePackageUninstall`
> (estándar en Laravel 13) falla si `symfony/polyfill-php83` ya fue eliminado: el autoloader
> generado sigue requiriendo ese archivo. Si ocurre, la solución es
> `rm -f vendor/composer/autoload_real.php vendor/composer/autoload_static.php`
> y reintentar `composer install`.

### 2.2 Frontend

- `package.json`: Vite `^8.3.0`, `laravel-vite-plugin ^3.2.0` (3.x es obligatorio para Vite 8),
  `tailwindcss ^4.3.3`, `@tailwindcss/vite ^4.3.3`, `@tailwindcss/forms ^0.5.11`.
  Se eliminaron `postcss`, `autoprefixer` (Tailwind 4 ya hace el prefijado).
- **Eliminados** `postcss.config.js` y `tailwind.config.js`: en Tailwind 4 la configuración vive en CSS.
- `resources/css/app.css` migrado a la sintaxis v4: `@import "tailwindcss"`, `@source`, `@plugin`,
  `@custom-variant dark` y un bloque `@theme` con los colores institucionales UES
  (`--color-ues-rojo`, `--color-ues-azul`, …) que antes estaban en `tailwind.config.js`.
- `vite.config.js`: se añadió el plugin `@tailwindcss/vite`.

### 2.3 Corrección colateral del pipeline de assets

El layout del candidato (`resources/views/candidate/layouts/app.blade.php:10` en `main`) pedía
`@vite([... 'resources/css/bootstrap-custom.css'])`, archivo que **existe en `resources/css/` pero
no estaba en `vite.config.js` ni en `public/build/manifest.json`**. Sin servidor de Vite corriendo,
`Illuminate\Foundation\Vite` lanza `ViteException` y **toda página del candidato, login y registro
devolvían error 500**. En esta rama:

- `bootstrap-custom.css` se agregó como entrada real de Vite (`vite.config.js`), por lo que ahora
  compila y aparece en el manifest.
- El layout quedó sin duplicados: cargaba Bootstrap por CDN dos veces y ejecutaba `@vite` dos veces
  (con un `@if (file_exists(...))` de por medio).
- Se eliminó `public/hot`, que apuntaba a `http://127.0.0.1:5173` con el servidor de Vite apagado y
  hacía que todos los assets compilados devolvieran 404 en local.

**Verificado:** `GET /login` → 200, `GET /register` → 200, los tres assets compilados se sirven con
su content-type correcto, y `GET /` sigue redirigiendo a `/login`.

---

## 2.4 Reseteo manual de contraseña de candidatos (implementado)

**Problema:** los candidatos no tienen recuperación de contraseña por correo (solo existe el broker
`users` en `config/auth.php`) y la regla de una sola oportunidad (`TestService::startTest`) implica
que un candidato que olvide su clave **antes** de rendir el test queda bloqueado sin ninguna vía de
autoservicio.

**Solución:** procedimiento de reseteo manual ejecutado por un administrador desde el panel, con
registro en `activity_logs`.

| Pieza | Archivo |
| --- | --- |
| Regla de negocio | `app/Services/CandidatePasswordResetService.php` |
| Acción del panel (modal + notificación) | `app/Filament/Resources/Candidates/Actions/ResetCandidatePasswordAction.php` |
| Puntos de acceso | `CandidatesTable` (acción de fila), `ViewCandidate` y `EditCandidate` (acciones de cabecera) |
| Broker declarado | `config/auth.php` → `passwords.candidates` |
| Pruebas | `tests/Feature/CandidatePasswordResetTest.php` (9 pruebas, 47 aserciones) |

**Cómo funciona**

1. El administrador abre **Candidatos**, usa la acción **Restablecer contraseña** (icono de llave) y
   confirma el modal.
2. El servicio genera una contraseña temporal de 12 caracteres, sin caracteres ambiguos
   (`0O1lI5S8B2Z`) para que sea fácil de transcribir al comunicarla.
3. La contraseña anterior deja de funcionar de inmediato (`Candidate` castea `password` como
   `hashed`, por lo que el nuevo valor se almacena hasheado).
4. Se muestra **una única vez** en una notificación persistente, con un botón *Copiar contraseña*.
   No se envía por correo y no puede consultarse después: la contraseña en claro nunca se persiste.
5. Se registra el evento `candidate_password_reset` en `activity_logs` con el administrador como
   `causer`, el candidato como `subject`, la IP, el user agent, el origen de la contraseña
   (`generated`/`manual`) y si el candidato ya había iniciado o completado el test.

**Decisiones relevantes**

- **Autorización:** la acción solo es visible para `admin`. Los `reporter` pueden ver la lista de
  candidatos pero no restablecer contraseñas.
- **No habilita un segundo intento:** el reseteo sirve para entrar al sistema; `test_completed` no se
  modifica. El modal avisa explícitamente cuando el candidato ya completó el test o tiene uno en
  curso.
- **Contraseña en claro fuera de los logs:** el registro guarda `password => ['changed' => true]`, sin
  hash ni contraseña. Hay una prueba dedicada que lo verifica.

**Verificación ejecutada:** 9 pruebas en verde (incluye la llamada real a la acción sobre el
componente Livewire `ListCandidates` y una aserción sobre el HTML servido por `/admin/candidates`),
más una corrida de humo contra la base de datos MySQL real que confirmó que la contraseña anterior
deja de servir, la nueva funciona y el registro de auditoría se crea con el causer/subject correctos.

**Pendiente relacionado:** si un **administrador** olvida su contraseña no hay ninguna vía hoy: el
panel no expone `->profile()` ni recuperación por correo, y no existe un comando de consola
(`php artisan` no registra ningún comando de reseteo). Se resuelve con un comando artesanal o con
acceso directo a la base de datos.

---

## 2.5 Extensión `intl` obligatoria (causa del error 500 en el panel)

**Síntoma:** cualquier página del panel falla con

```
RuntimeException
vendor/laravel/framework/src/Illuminate/Support/Number.php:476
The "intl" PHP extension is required to use the [format] method.
```

**Causa:** Filament 4 usa `Illuminate\Support\Number::format()` (por ejemplo en
`vendor/filament/support/src/helpers.php`), que requiere `ext-intl`. El PHP 8.4 del sistema tiene
`php8.4-common` pero **no `php8.4-intl`**: en `/usr/lib/php/20240924/` no existe `intl.so` (solo está
el de la API de PHP 8.3). Lo mismo ocurriría en producción si no se instala la extensión.

**Solución definitiva (servidor):**

```bash
sudo apt-get install -y php8.4-intl      # o: sudo apt-get install -y php8.3-intl
sudo systemctl restart php8.4-fpm        # si se usa FPM; con artisan serve basta reiniciarlo
```

**Solución mientras tanto (sin root):** el lanzador `bin/php` ejecuta la aplicación con un PHP 8.4
que ya incluye `intl` (binario estático en `.tools/php84/`, ignorado por git):

```bash
./bin/php artisan serve          # en lugar de: php artisan serve
./bin/php artisan route:list
./bin/php test                   # atajo de ./bin/php artisan test
```

`composer.json` declara ahora `"ext-intl": "*"`, de modo que `composer install` avisa si falta la
extensión en lugar de fallar en tiempo de ejecución.

**Pruebas:** `tests/Feature/AdminPanelSmokeTest.php` falla con un mensaje explícito si el PHP que
ejecuta los tests no tiene `intl`, y comprueba que `/admin/candidates` y `/admin/test-results`
respondan 200 sin ese error.

> **Ojo:** `.tools/php84/php` no está versionado (está en `.gitignore`). Si se pierde, `bin/php`
> explica cómo volver a descargarlo.

---

## 2.6 Auditoría de exportaciones (evento `exported`)

**Requisito:** poder auditar quién descargó qué. El rol `reporter` **sí puede** exportar el informe
PDF individual con el diagnóstico completo (alcance confirmado), y cada exportación debe quedar
registrada en `activity_logs`.

**Implementación:**

| Pieza | Archivo |
| --- | --- |
| Helper de auditoría | `ActivityLogService::logExport()` y `ActivityLogService::EVENT_EXPORTED` |
| Exportación PDF individual | `app/Filament/Resources/TestResults/Tables/TestResultsTable.php` (acción `pdf`) |
| Exportación CSV de candidatos | `app/Filament/Resources/Candidates/Tables/CandidatesTable.php` (acción masiva `export`) |
| Pruebas | `tests/Feature/TestResultPdfExportTest.php` (6 pruebas, 41 aserciones) |

**Qué se registra:** `event = "exported"`, el usuario como `causer` (admin o reporter), el registro
exportado como `subject`, y en `properties`: `export_format` (`pdf`/`csv`), `filename`,
`exported_at`, `exported_by_id`, `exported_by_name`, `exported_by_role`, `ip_address`, `user_agent` y
los datos del informe (candidato, puntaje total, percentil, rango diagnóstico, `report_scope`). La
exportación CSV añade `candidate_count`, `candidate_ids` e `includes_pii`.

**Decisiones y detalles relevantes**

- **Solo se audita una exportación real:** el PDF se renderiza *antes* de registrar y de responder; si
  la generación falla, no se escribe un registro falso, se avisa por notificación y el error queda en
  el log del servidor.
- **El registro se escribe antes de entregar el archivo.** Si el navegador aborta la descarga a mitad
  de camino, el log queda igualmente: se prefirió un registro de más antes que uno de menos en una
  traza de auditoría.
- **Se corrigió un 500 real de la acción PDF:** cuando el candidato está eliminado lógicamente,
  `$record->candidate` es `null` y el nombre del archivo reventaba. Ahora se muestra una notificación
  clara en lugar de un error de servidor.
- **La exportación CSV también se audita**, aunque no se pidió explícitamente: es la otra exportación
  del panel y contiene datos personales, así que auditar solo una quedaba incoherente.

**Nota de alcance:** el evento `exported` es el que exige Laravel y el que ya estaba previsto en la
documentación del modelo (`ActivityLog` menciona `'exported'` entre los eventos). El *formato* de la
exportación se distingue dentro de `properties.export_format`, no en el nombre del evento.

---

## 2.7 Límite de intentos en registro y login (implementado)

**Problema:** `POST /register` es la única ruta que crea cuentas sin autenticación y **no tenía
ningún límite** (hallazgo 7 de la sección 4). Un bot podía crear candidatos en masa o usar el
formulario como endpoint de sondeo.

**Solución elegida: throttling, sin captcha.** Se descartó el captcha porque añade una dependencia
externa (o un servicio con claves), depende de JavaScript, agrega fricción a un candidato que se
registra una sola vez y no aporta nada frente al abuso automatizado simple que aquí se quiere frenar.
El límite por servidor es más simple, no rompe la accesibilidad y es verificable.

| Pieza | Archivo |
| --- | --- |
| Definición de los límites | `app/Providers/AppServiceProvider.php` → `RateLimiter::for('register')` |
| Aplicación en la ruta | `routes/web.php` → `->middleware('throttle:register')` |
| Respuesta del 429 | `bootstrap/app.php` → `$exceptions->render(ThrottleRequestsException::class)` |
| Aviso en pantalla | `resources/views/auth/register.blade.php` (bloque de mensajes flash) |
| Pruebas | `tests/Feature/CandidateRegistrationThrottleTest.php` (8 pruebas, 38 aserciones) |

**Los dos límites**

- **5 registros por hora y por IP** — frena la creación masiva de cuentas desde un mismo origen. Un
  candidato real se registra una sola vez, así que no le afecta.
- **3 intentos por hora sobre el mismo DUI/NIT** — evita que se roten direcciones IP (o que se salga
  por una red compartida) para insistir sobre una misma identidad.

**Comportamiento ante el bloqueo**

Laravel devuelve 429. En vez de la página de error sin contexto, se vuelve al formulario con
«Se alcanzó el límite de intentos de registro. Vuelve a intentarlo en N minuto(s).», conservando el
código 429 y la cabecera `Retry-After`. Si la petición espera JSON se mantiene la respuesta estándar.

**Decisión sobre la cuota:** el límite se aplica **antes** de validar el formulario, así que un bot
que envía datos inválidos también consume su cuota. Es deliberado: impide usar el endpoint como
sondeo indefinido. Hay una prueba que lo fija.

**Bug encontrado al implementarlo:** la vista de registro **no mostraba los mensajes flash**, solo
errores de validación por campo. Sin ese arreglo, el candidato bloqueado volvía al formulario sin
ninguna explicación (el rate limiting funcionaba, pero era invisible y confuso). Se añadieron los
bloques de `session('error')` y `session('success')`.

**Bug del mismo tipo en la vista de login:** tampoco renderizaba mensajes flash (solo
`session('status')`), así que un candidato bloqueado volvía al formulario sin explicación. Corregido
igual que en el registro.

> **Nota de despliegue:** el contador vive en la caché. Con `CACHE_STORE=database` (el valor actual)
> el límite es compartido entre procesos, que es lo correcto. Si en algún momento se cambia a `array`,
> el límite deja de ser efectivo entre peticiones.

---

## 2.8 Límite de intentos en el login de candidatos (implementado)

**Problema:** `POST /login` no tenía ningún límite (hallazgo 7 de la sección 4), así que se podían
probar contraseñas sin fin contra la cuenta de un candidato: fuerza bruta pura.

| Pieza | Archivo |
| --- | --- |
| Definición de los límites | `app/Providers/AppServiceProvider.php` → `RateLimiter::for('login')` |
| Aplicación en la ruta | `routes/web.php` → `->middleware('throttle:login')` |
| Respuesta del 429 | `bootstrap/app.php` (handler compartido con el registro) |
| Aviso en pantalla | `resources/views/auth/login.blade.php` |
| Pruebas | `tests/Feature/CandidateLoginThrottleTest.php` (8 pruebas, 50 aserciones) |

**Los dos límites**

- **5 intentos fallidos por minuto y por IP** — frena la prueba masiva de credenciales desde un mismo
  origen. El umbral es holgado a propósito: varias personas pueden compartir IP (oficina, universidad,
  red móvil) y un usuario legítimo rara vez falla cinco veces en un minuto.
- **5 intentos fallidos por cada 15 minutos sobre el mismo identificador** (email o DUI/NIT) — cubre la
  fuerza bruta **distribuida**, que rota direcciones IP para insistir sobre una sola cuenta. La ventana
  es más larga porque este es el vector peligroso: adivinar la clave de un candidato concreto.

**Solo cuentan los intentos fallidos.** El middleware de Laravel usa `afterCallback` para el
incremento, y se verificó en `vendor/.../ThrottleRequests.php:170-173` que una respuesta correcta no
consume cuota. Un candidato que entra bien nunca se ve afectado; hay una prueba que lo fija.

**Mientras dura el bloqueo, incluso la contraseña correcta es rechazada.** Es deliberado: si el
bloqueo se levantase al acertar, el formulario seguiría sirviendo como oráculo para adivinar la clave.

**El mensaje de espera se adapta a la ventana:** el handler compartido ahora expresa la espera en
segundos, minutos u horas, porque el login bloquea por minutos y el registro por horas.

---

## 3. Hallazgos verificados del panel de administración

| # | Hallazgo | Evidencia |
| --- | --- | --- |
| 1 | **El rol `reporter` puede borrar definitivamente y restaurar** candidatos, sesiones y resultados. Los recursos solo sobrescriben `canViewAny/canCreate/canEdit/canDelete/canDeleteAny`; `ForceDeleteBulkAction`/`RestoreBulkAction` consultan `canForceDeleteAny`/`canRestoreAny`, que no existen y por defecto se permiten. | `CandidatesTable.php:109-110`, `TestResultsTable.php:137-138`, `TestSessionsTable.php:96-97` |
| 2 | **`TestQuestionForm` escribe una columna inexistente**: el repeater de opciones incluye un toggle `is_correct`, pero `answer_options` no tiene esa columna desde la migración `2026_02_09_030948`. Guardar una pregunta lanza `SQLSTATE[42S22]`. `AnswerOption::$fillable` conserva `is_correct` y `text` (tampoco existe). | `TestQuestionForm.php:88`, `AnswerOption.php:15-16` |
| 3 | **La descarga de PDF no maneja errores** y navega `$result->candidate->name` sin protección; `Candidate` usa *soft deletes*, así que un resultado de un candidato borrado produce 500. Lo mismo en la vista del reporte. | `TestResultsTable.php:106-129,127`, `raven-result.blade.php:6` |
| 4 | **Doble prefijo de rutas de imagen**: la BD guarda `matrices/A/A1-0.png`, la tabla antepone `test-images/` y el formulario sube a `test-images/matrices`. Hoy funciona solo porque el árbol de assets está duplicado; las imágenes nuevas se resuelven como `test-images/test-images/...`. | `TestQuestionsTable.php:47`, `TestQuestionForm.php:58`, `RavenTestSeeder.php:62` |
| 5 | **Sin validación de unicidad** que la BD sí impone: `unique(test_series_id, question_number)`, `test_series.code` y `unique(test_question_id, option_number)`. Los duplicados revientan con error SQL en lugar de un mensaje de validación. | `TestQuestionForm.php:32-38,69-99`, `TestSeriesForm.php:20-26` |
| 6 | `TestSessionResource` se declara de solo lectura, pero su tabla deja vivos los borrados masivos y las rutas `create`/`edit` se registran con un formulario vacío. | `TestSessionResource.php:47-65`, `TestSessionsTable.php:95-97`, `TestSessionForm.php:7-15` |
| 7 | **N+1 generalizado:** no hay *eager loading* en `app/Filament`. El accesor `progress_percentage` ejecuta un `count()` por fila. | `TestResultsTable.php:31,74`, `TestSessionsTable.php:26,57`, `TestSession.php:75-80` |
| 8 | `CheckPanelAccess` **nunca se registra** (ni en `bootstrap/app.php` ni en el panel) y es redundante: el middleware `Authenticate` de Filament ya aplica `canAccessPanel`. | `bootstrap/app.php:14-17`, `AdminPanelProvider.php:52-55` |
| 9 | El panel no ofrece **cambio de contraseña ni recuperación**: solo existen `admin/login` y `admin/logout`. Faltan `navigationGroups()`, avatar, logo y tema propio. | `AdminPanelProvider.php:22-57`, `route:list` |
| 10 | **Sin protección del último administrador**: el borrado masivo de usuarios puede dejar el panel inaccesible. Además `$recordTitleAttribute = 'Usuario'` es una etiqueta, no un atributo, y quedan restos de la API v3 (`actions()`, `bulkActions()`, import de `Filament\Tables\Actions\Action`). | `UsersTable.php:104,110,112,114`, `UserResource.php:28`, `RecentSessionsWidget.php:8,92` |

Los 5 widgets renderizan correctamente con la base de datos vacía (ceros y datasets vacíos, sin
excepciones). El único SQL crudo es `DB::raw('DATE(completed_at)')` en `TestsTrendWidget.php:26-30`.

---

## 4. Hallazgos verificados del flujo del candidato

| # | Hallazgo | Evidencia |
| --- | --- | --- |
| 1 | **`bootstrap-custom.css` fuera del manifest** → error 500 en todas las páginas públicas. **Corregido en esta rama** (sección 2.3). | `app.blade.php:10` (antes), `public/build/manifest.json` |
| 2 | **Salida de build no versionada + `public/hot` obsoleto** → todos los assets en 404. `public/build/` y `public/hot` están en `.gitignore`; el `hot` apuntaba a un Vite apagado. **Corregido** (se eliminó `public/hot`). | `.gitignore:27-28` |
| 3 | **Las figuras del test no están versionadas**: `storage/app/public/.gitignore` ignora todo y solo se confirma `test-images/`; además el prefijo del panel (`test-images/`) y el que usa la vista del candidato no coinciden. En un clon limpio las láminas dan 404 mientras que la vista previa del admin sí funciona. | `storage/app/public/.gitignore:1`, `TestQuestionsTable.php:47`, `question.blade.php:73` |
| 4 | **`AnswerOption::$fillable` omite `option_image_path`**, así que `RavenTestSeeder` la descarta silenciosamente al resembrar (la columna es `NOT NULL`). | `AnswerOption.php:12-17`, `RavenTestSeeder.php:120-126` |
| 5 | **Las opciones de respuesta son solo para ratón**: son `<div>` con listener de click, sin `role`, `tabindex`, `aria-checked` ni manejo de teclado. | `question.blade.php:90`, `test-timer.js:56-61` |
| 6 | **Desajuste `hidden` (Tailwind) vs `d-none` (Bootstrap)** en el overlay y el spinner: nunca se muestran. | `test-timer.js:242,244,250` vs `question.blade.php:132` |
| 7 | **`login` y `register` sin rate limiting**: `throttle:test-post` solo cubre las rutas del test; permiten fuerza bruta sin límite. | `routes/web.php:18,22` vs `:33,38,41` |
| 8 | **El cronómetro del cliente puede descartar una respuesta**: el conteo es un delta contra el reloj del navegador, así que un reloj adelantado provoca que el POST sea rechazado por el chequeo de timeout del servidor y el candidato pierde el ítem. El endpoint `GET /test/timer`, que existe para resincronizar, nunca se llama. | `test-timer.js:90-92`, `TestController.php:143-151`, `routes/web.php:43` |
| 9 | **`POST /test/timeout` completa el test sin validar el tiempo real**: cualquier candidato autenticado puede terminar el test antes de tiempo. | `TestController.php:244-245` |
| 10 | **Middleware muerto y roto**: los alias `candidate.auth` y `test.not.completed` (declarados en `bootstrap/app.php:16-17`) no se usan en ninguna ruta, y `CandidateAuth` no importa el facade `Auth` ni apunta a una ruta existente (`candidate.login` no está definida). Además `RedirectIfAuthenticated` envía a `/dashboard`, que no existe (el panel vive en `/admin`). | `bootstrap/app.php:16-17`, `CandidateAuth.php:19-20`, `RedirectIfAuthenticated.php:30` |

Comprobaciones que **no** resultaron ser defectos: no hay CSRF ausente (todos los POST están en el
grupo `web` con token), no hay `time_spent` manipulable desde el cliente (el servidor lo recalcula en
`TestController.php:168`), no hay `<title>` duplicado, y el candidato no puede reiniciar el test
(`TestService.php:19-21,30-39`).

### Pruebas y calidad

**Estado al cerrar esta rama:** 34 pruebas en verde y 1 en rojo.

- `Tests\Feature\ExampleTest` **falla desde antes de esta rama**: espera 200 en `/` y recibe 302
  (la raíz redirige a `/login`). Es un ejemplo del esqueleto, no cubre nada real.
- Cobertura añadida en esta rama: reseteo de contraseña de candidatos (9), auditoría de exportaciones
  (6), límite de intentos del registro (8), límite de intentos del login (8) y smoke del panel (2).
- **Sigue sin cobertura** el flujo del test (responder, timeout, reanudación), el cálculo de resultados,
  el percentil y el control de tiempo. `database/factories/` solo tiene `UserFactory`: no hay factorías
  para `Candidate`, `TestSession`, `TestQuestion`, `TestAnswer` ni `TestResult`, y cada prueba nueva las
  crea a mano (motivo por el que la cobertura del flujo del test es más costosa de lo que debería).
- Localización: cero llamadas a `__()`/`trans()` en `app/Filament` y en las vistas del candidato;
  todo el texto está fijo en español. El texto de bienvenida dice "una de las 6 opciones" aunque las
  series C, D y E tienen 8 (`welcome.blade.php:71`).

---

## 5. Estado de la base de datos (observación de solo lectura)

- `test_questions.matrix_image_path` = `matrices/A/A1-0.png` en las 60 filas.
- `answer_options.option_image_path` = `options/A/A1/A1-1.png`.
- `users`: 2 administradores y 1 reporter.
- `app/Policies/` no existe y `AppServiceProvider` no define `Gate::before`, de ahí que cualquier
  habilidad no sobrescrita quede permitida por defecto (origen del hallazgo 1 del panel).

**No verificable desde el código:** si el despliegue de producción ejecuta `npm run build` o sirve
assets con un Vite corriendo, y el contenido real de los archivos bajo `public/build/` que hoy solo
existen en este entorno de trabajo.

---

## 6. Pendientes priorizados (no incluidos en esta rama)

**Bloqueantes de negocio**

1. Restringir `canForceDeleteAny`/`canRestoreAny` por rol (hallazgo 1 del panel).
2. Unificar la convención de rutas de imagen y versionar las láminas del test (hallazgos 3 y 4 del candidato).

**Integridad de datos**

4. Decidir el modelo de `answer_options`: o se agrega `is_correct`/`text` a la tabla, o se quita del
   formulario y del `$fillable` (hallazgo 2 del panel).
5. `option_image_path` en `$fillable` de `AnswerOption` (hallazgo 4 del candidato).
6. Validaciones de unicidad y de rangos diagnósticos (hallazgo 5 del panel).

**Experiencia y accesibilidad**

7. Accesibilidad por teclado y ARIA en las opciones de respuesta (hallazgo 5 del candidato).
8. Unificar la convención de clases (`hidden`/`d-none`) y arreglar el overlay (hallazgo 6 del candidato).
9. Resincronización periódica del cronómetro y validación real en `/test/timeout` (hallazgos 8 y 9 del candidato).

**Mantenimiento**

10. Eager loading en las tablas del panel, y perfil/recuperación de contraseña **de los
    administradores** (hallazgos 7 y 9 del panel). El reseteo de candidatos ya está implementado
    (sección 2.4).
11. Retirar el middleware muerto (`CandidateAuth`, alias sin uso, `CheckPanelAccess`) y corregir el
    destino de `RedirectIfAuthenticated` (hallazgo 10 del candidato).
12. Cobertura de pruebas del flujo, el cálculo y el timer, y corrección de `ExampleTest`.

**Resuelto en esta rama**

- Reseteo manual de la contraseña de candidatos por parte del administrador, con registro en
  `activity_logs` (sección 2.4). Cubre el bloqueo de un candidato que olvida su clave antes de
  rendir el test.
- Error 500 del pipeline de assets y `public/hot` obsoleto (hallazgos 1 y 2 del candidato).
- Error 500 del panel por falta de `ext-intl`, con lanzador `bin/php` y declaración en
  `composer.json` (sección 2.5).
- Auditoría de exportaciones con el evento `exported`, para PDF individual y CSV de candidatos, más
  el 500 de la acción PDF cuando el candidato está eliminado lógicamente (sección 2.6).
- Límite de intentos en el registro público (5 por hora y por IP, 3 por DUI/NIT) con aviso claro al
  candidato y sin captcha (sección 2.7). Incluye el arreglo de la vista de registro, que no mostraba
  los mensajes flash.
- Límite de intentos en el login de candidatos (5 fallidos por minuto y por IP, 5 por 15 minutos por
  identificador), con la misma lógica sin captcha (sección 2.8). Incluye el mismo arreglo de vista:
  el login tampoco mostraba mensajes flash.

**Nota sobre las pruebas del panel:** el test de auditoría de exportaciones crea los resultados y
sesiones que necesita, pero la base de datos **local** no tiene resultados reales (`test_results` = 0),
así que la verificación end-to-end del PDF se hizo con datos tipados en SQLite y no contra datos
vivos.
