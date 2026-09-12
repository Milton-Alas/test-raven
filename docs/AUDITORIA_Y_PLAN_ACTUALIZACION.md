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

- `tests/` contiene únicamente los dos ejemplos del esqueleto. `Tests\Feature\ExampleTest` **falla
  desde antes de esta rama**: espera 200 en `/` y recibe 302 (la raíz redirige a `/login`).
- No existe cobertura alguna del flujo del test, del cálculo de resultados, del percentil ni del
  control de tiempo. `database/factories/` solo tiene `UserFactory`, no hay factorías para
  `Candidate`, `TestSession`, `TestQuestion` ni `TestAnswer`.
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
3. Rate limiting en `login`/`register` (hallazgo 7 del candidato).

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

10. Eager loading en las tablas del panel y perfil/recuperación de contraseña (hallazgos 7 y 9 del panel).
11. Retirar el middleware muerto (`CandidateAuth`, alias sin uso, `CheckPanelAccess`) y corregir el
    destino de `RedirectIfAuthenticated` (hallazgo 10 del candidato).
12. Cobertura de pruebas del flujo, el cálculo y el timer, y corrección de `ExampleTest`.
