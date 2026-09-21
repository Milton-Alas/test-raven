# RNF-09 — Confidencialidad de los datos

**Estado:** implementado y verificado
**Normativa de referencia:** Ley de Protección de Datos Personales de El Salvador (D.L. 144/2024)

Este documento acredita cada sub-requisito de RNF-09: qué se hizo, dónde vive el código y con
qué prueba se demuestra. Cada prueba citada falla si el requisito deja de cumplirse, no es
una descripción de intenciones.

---

## Resumen

| Sub-requisito | Estado | Pruebas |
| --- | --- | --- |
| 09.01 Cifrado en reposo del identificador | Implementado | 12 |
| 09.02 Protección de credenciales | Implementado | 3 (reseteo por admin) + 1 integrada en 09.01 |
| 09.03 Separación de acceso candidato/administración | Implementado | 8 |
| 09.04 Retención y supresión/disociación | Implementado | 8 |
| 09.05 Aplicación automática y registro | Implementado | 4 |

Los tres primeros ya estaban cubiertos por el diseño original del sistema; 09.01 se formalizó con
cifrado real y 09.04/09.05 se construyeron en esta etapa.

---

## 09.01 — Cifrado en reposo

> El identificador `dui_nit` se almacena cifrado mediante AES-256-CBC. La búsqueda y validación de
> unicidad se realizan mediante un índice seguro `dui_nit_hash`, sin necesidad de descifrar el valor.

### Cómo se cumple

El identificador se guarda en **dos representaciones complementarias**, y la razón de que sean dos
es técnica, no capricho:

| Representación | Columna | Qué es | Para qué |
| --- | --- | --- | --- |
| Cifrado | `dui_nit` (TEXT) | AES-256-CBC con `APP_KEY` | Confidencialidad en reposo |
| Índice | `dui_nit_hash` (CHAR(64), UNIQUE) | HMAC-SHA256 determinista | Buscar y validar unicidad sin descifrar |

El cifrado usa **IV aleatorio**, así que el mismo DUI produce un texto cifrado distinto en cada
registro. Eso es lo correcto para confidencialidad, pero hace imposible buscar por igualdad: de ahí
el índice. El índice es un **HMAC con `APP_KEY`** y no un `sha256` simple a propósito: el DUI
salvadoreño tiene del orden de 10⁸ combinaciones válidas, así que un hash sin clave se revierte con
una tabla precalculada en minutos y anularía el cifrado.

`config/app.php:98` ya declara `'cipher' => 'AES-256-CBC'`, que es el que aplica `Illuminate\Support\Facades\Crypt`.

### Dónde vive

| Pieza | Archivo |
| --- | --- |
| Cifrado, índice, normalización y enmascarado | `app/Support/DuiNitCipher.php` |
| Cifrado transparente y cálculo del índice | `app/Models/Candidate.php` (accessor `duiNit()` y hook `saving`) |
| Búsqueda por índice | `Candidate::findByDuiNit()` |
| Migración y conversión de datos existentes | `database/migrations/2026_09_20_000000_encrypt_candidate_dui_nit.php` |

### Decisiones relevantes

- **La conversión de datos existentes va dentro de la migración**, con verificación de duplicados
  previa a la creación del índice único: si dos candidatos compartieran identificador, la migración
  falla con un mensaje explícito en lugar de dejar la base a medias.
- **El índice se recalcula en cada guardado**, así que nunca queda desincronizado con el valor cifrado.
- **El identificador se enmascara en los listados** (`dui_nit_masked` → `•••••4567`) y no se
  serializa nunca al cliente (`dui_nit_hash` está en `$hidden`).
- **El límite de intentos del registro dejó de usar el DUI en claro como clave de caché**
  (`AppServiceProvider.php`); ahora usa el mismo índice seguro.

### Pruebas

`tests/Feature/Rnf0901DuiNitEncryptionTest.php` — 12 pruebas, 33 aserciones. Las que sostienen el
requisito:

- El valor almacenado **no contiene el DUI** y es el texto cifrado (leído con el query builder, sin
  que el modelo lo descifre).
- El modelo descifra al leer.
- Dos cifrados del mismo valor **difieren** (IV aleatorio) y ambos descifran correctamente.
- El índice es determinista, de 64 caracteres, e igual con y sin guiones.
- El índice **no coincide** con un `sha256` simple (prueba de que es HMAC).
- La búsqueda funciona **sin descifrar**: la consulta filtra por `dui_nit_hash`.
- El login por DUI/NIT funciona con el valor cifrado, y rechaza la contraseña incorrecta.
- No se puede registrar dos veces el mismo identificador (con formatos distintos).
- La base impone el índice único aunque se inserte saltándose el modelo.

---

## 09.02 — Protección de credenciales

> Las contraseñas se almacenan mediante hash bcrypt irreversible. El restablecimiento de credenciales
> se realiza mediante un administrador y queda registrado para fines de auditoría.

### Cómo se cumple

- **bcrypt irreversible:** ambos modelos (`Candidate`, `User`) declaran el cast `'password' => 'hashed'`,
  que aplica `bcrypt` con el coste de `BCRYPT_ROUNDS` (12 por defecto).
- **Restablecimiento solo por administrador:** la acción `ResetCandidatePasswordAction` del panel
  genera una contraseña temporal y la muestra **una única vez**. Está visible únicamente para el rol
  `admin`; el rol `reporter` no la ve. Los candidatos **no tienen recuperación por correo**: es un
  procedimiento manual y auditado.
- **Registro de auditoría:** cada reseteo escribe el evento `candidate_password_reset` en
  `activity_logs`, con el administrador como `causer`, el candidato como `subject` y metadatos de la
  operación. **La contraseña en claro nunca se persiste**, ni siquiera en el log.

### Pruebas

`tests/Feature/CandidatePasswordResetTest.php` — 9 pruebas, 47 aserciones:

- La contraseña anterior deja de funcionar y la nueva funciona.
- El evento queda registrado con `causer` (admin) y `subject` (candidato) correctos.
- **La contraseña en claro no aparece en ningún registro de actividad.**
- El rol `reporter` no ve la acción.
- La contraseña mostrada en pantalla es la que quedó realmente almacenada.

`Rnf0901DuiNitEncryptionTest` verifica además que el algoritmo almacenado es `bcrypt`.

---

## 09.03 — Separación de acceso candidato/administración

> El acceso del candidato se limita a su propia sesión de test, mientras que los resultados, datos
> psicométricos y demás información sensible se restringen al panel administrativo mediante
> autorización por roles.

### Cómo se cumple

- **Guards separados:** `web` (usuarios) y `candidate` (candidatos) en `config/auth.php`. Una sesión
  de candidato **no da acceso al panel**.
- **Autorización por roles:** `User::canAccessPanel()` exige rol `admin`, `reporter` o `evaluador`
  **y** cuenta activa.
- **Alcance de cada rol:**
  - `admin`: acceso completo a la consulta, a la operación diaria (candidatos y resultados) y a la
    gestión de usuarios del panel.
  - `reporter`: solo consulta (candidatos, sesiones, resultados) y exportación auditada, incluida la
    masiva a Excel/CSV. No entra a usuarios, instrumento ni páginas de edición.
  - `evaluador`: solo consulta (candidatos, sesiones, resultados e instrumento) y descarga del
    informe PDF individual, también auditada. No tiene exportación masiva ni acceso a usuarios.
- **Las rutas del test resuelven siempre la sesión del candidato autenticado**: no existe ninguna que
  acepte el identificador de una sesión ajena.
- **El instrumento está en modo consulta para todos los roles**, `admin` incluido: series, reactivos,
  baremos y rangos diagnósticos se consultan desde el panel, pero no se crean, editan ni eliminan ahí
  (ver `AUDITORIA_Y_PLAN_ACTUALIZACION.md`, secciones 2.4 y el commit del banco). Los cambios reales
  entran por seeder o migración, con control de versiones.
- **Los datos psicométricos no se exponen al candidato:** las rutas del test devuelven preguntas y
  estado del cronómetro, nunca percentiles ni diagnósticos.

### Pruebas

`tests/Feature/Rnf0903AccessSeparationTest.php` — 8 pruebas, 27 aserciones:

- Sin sesión no se accede al flujo del test.
- Un candidato **no puede escribir respuestas en la sesión de otro** (no se crea ninguna fila).
- Un candidato autenticado **no puede entrar al panel** (`/admin` responde redirección y el guard
  `web` sigue sin autenticar).
- Los resultados psicométricos no son accesibles sin un rol autorizado; un usuario **desactivado**
  recibe 403.
- El `reporter` entra a los recursos de consulta y recibe 403 en usuarios, reactivos, series, baremos
  y rangos.
- El `reporter` no puede abrir las páginas de edición de resultados ni de candidatos.
- El `admin` sí accede a los datos psicométricos.
- La API del candidato no devuelve `percentile` ni `diagnostic`.

La matriz completa de los tres roles, incluidas las exportaciones y el instrumento de solo lectura, se
comprueba en `tests/Feature/PanelRolePermissionsTest.php` y `tests/Feature/TestBankUiRulesTest.php`.

---

## 09.04 — Retención y supresión de datos

> El sistema permite configurar períodos de retención para datos psicométricos, información personal,
> registros de actividad y datos técnicos, aplicando supresión o disociación al finalizar dichos
> períodos.

### Cómo se cumple

Las cuatro categorías del requisito están configuradas en `config/retention.php`, con plazos
ajustables por variables de entorno:

| Categoría | Qué alcanza | Plazo por defecto | Acción |
| --- | --- | --- | --- |
| `personal` | Nombre, correo, DUI/NIT, edad, ocupación, nivel educativo | 1825 días (5 años) | **disociar** |
| `psicometrico` | Resultados, puntajes, percentiles, diagnósticos, respuestas | 1825 días (5 años) | **disociar** |
| `actividad` | Registros de auditoría | 730 días (2 años) | **suprimir** |
| `tecnico` | IP, user agent, datos del navegador | 180 días (6 meses) | **suprimir** |

**La decisión de disociar en lugar de suprimir los datos psicométricos es deliberada y está
argumentada:** suprimir los resultados rompería la serie histórica con la que se calibra el baremo.
La disociación cumple igual el principio de minimización —el dato deja de ser atribuible a una
persona— y conserva la validez estadística. Los datos de actividad y técnicos sí se suprimen, porque
no son datos de investigación.

**Qué hace exactamente la disociación** de un candidato vencido:

- `name` → `Candidato disociado #{id}`
- `email` → `disociado+{id}@anonimo.invalid` (dominio reservado, imposible de confundir con real)
- `dui_nit` y `dui_nit_hash` → `NULL` (el identificador desaparece por completo)
- `age` → `0`, `occupation` y `education_level` → `NULL` (cuasi-identificadores)
- `disociado_at` → sello temporal de cuándo se aplicó

Y **se conservan** los datos psicométricos asociados (puntaje, percentil, diagnóstico), que quedan
desligados de toda persona. La marca `disociado_at` impide que un registro ya procesado se vuelva a
procesar, y es la constancia de cuándo ocurrió.

En los **datos técnicos** no se elimina la fila: la sesión de test es evidencia del proceso
psicométrico y solo pierde la traza técnica (IP, user agent, browser info → `NULL`).

### Alcance declarado (exclusiones)

`config/retention.php` documenta explícitamente lo que la política **no** alcanza, para que no queden
zonas grises ante una auditoría:

- **El banco de ítems** (series, reactivos, opciones, tablas normativas) es el instrumento, no un dato
  personal, y es inmutable: borrarlo invalidaría resultados históricos.
- **Los usuarios administradores** siguen el ciclo de vida que defina la institución.
- **Las copias de seguridad** siguen su propio ciclo. La retención se aplica sobre la base activa; un
  respaldo ya generado conserva los datos hasta que su propio ciclo lo elimine. Esto debe tenerse
  presente al declarar plazos de cumplimiento.

### Dónde vive

| Pieza | Archivo |
| --- | --- |
| Configuración de plazos y acciones | `config/retention.php` |
| Aplicación de las políticas | `app/Services/RetentionService.php` |
| Marca de disociación | `database/migrations/2026_09_20_000200_add_disociado_at_to_candidates.php` |

### Pruebas

`tests/Feature/Rnf09RetentionTest.php` — 8 pruebas que cubren 09.04 (las otras 9 cubren 09.05):

- Disocia **solo** lo que superó su plazo; lo reciente no se toca.
- La disociación elimina **todos** los identificadores directos (nombre, correo, DUI cifrado, índice,
  ocupación, nivel educativo, edad).
- **Conserva los datos psicométricos**: el puntaje y el percentil sobreviven, desligados de la persona.
- Un candidato ya disociado **no se procesa dos veces**.
- Los registros de actividad vencidos se suprimen; los recientes permanecen.
- Los datos técnicos se eliminan **sin borrar la sesión**.
- Los plazos son configurables en caliente.
- Una categoría sin plazo configurado **no se aplica** (`null` no significa cero días).

---

## 09.05 — Aplicación automática de la retención

> El sistema ejecuta automáticamente las políticas de retención configuradas, eliminando o disociando
> los datos que hayan superado su período establecido y registrando las operaciones realizadas.

### Cómo se cumple

- **Ejecución automática:** tarea programada en `routes/console.php`, a diario a las 03:00, con
  `withoutOverlapping()` y `onOneServer()`.
  Requiere el planificador activo en el servidor:
  `* * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1`
- **Ejecución manual:** `php artisan retention:apply` con `--categoria=`, `--dry-run` y `--force`.
- **Registro de las operaciones:** tabla `retention_logs`, con una fila por categoría y ejecución:

  | Campo | Contenido |
  | --- | --- |
  | `categoria`, `accion` | Qué política se aplicó |
  | `dias_retencion`, `fecha_corte` | Bajo qué plazo y con qué fecha de corte |
  | `registros_afectados`, `detalle` | Alcance real de la operación |
  | `origen` | `schedule` (automático) o `manual` |
  | `simulacion` | Si fue una ejecución sin efectos |
  | `resultado`, `error`, `duracion_ms` | Resultado y diagnóstico |

- **El registro no guarda datos personales**: solo conteos y metadatos, de modo que la propia
  evidencia de la retención no se convierte en un dato sensible.

### Modo simulación

`--dry-run` recorre exactamente la misma lógica sin escribir cambios, y **deja constancia** de la
evaluación (con `simulacion = true` y el número de registros que se verían afectados). Sirve para
revisar el alcance antes de aplicarlo y para demostrar la política sin ejecutarla.

### Pruebas

- Cada operación queda registrada con categoría, acción, plazo, fecha de corte, afectados y resultado.
- **El registro de retención no contiene datos personales** (ni el DUI, ni el correo, ni el nombre).
- El modo simulación **no modifica nada** pero sí queda registrado.
- El comando aplica la retención y reporta; en simulación no toca datos.
- El comando acepta una categoría concreta y rechaza una inexistente.
- El comando respeta el interruptor general (`RETENCION_ACTIVA=false`).
- **La tarea está programada**: se comprueba la expresión cron real (`0 3 * * *`).

---

## Riesgos y consideraciones de operación

Deben conocerse antes de poner esto en producción:

1. **`APP_KEY` es crítica de continuidad.** Si se pierde, se pierden los DUI/NIT cifrados y los
   `dui_nit_hash` dejan de coincidir: no se podría buscar ni iniciar sesión por DUI. Debe respaldarse
   junto con la base de datos. Para rotarla sin romper lo existente, Laravel soporta
   `APP_PREVIOUS_KEYS`.
2. **El cifrado protege contra la fuga de la base, no contra quien tenga `APP_KEY` y la base a la
   vez.** Es el modelo de amenaza que cubre el requisito; conviene declararlo así.
3. **La retención no alcanza los respaldos.** Un backup generado antes de una disociación conserva los
   datos personales hasta que su propio ciclo lo elimine.
4. **Los plazos por defecto son una propuesta**, no una imposición legal: 5 años para datos personales
   y psicométricos, 2 para auditoría y 6 meses para datos técnicos. La institución debe validarlos
   según su propia política y la finalidad del tratamiento.
5. **La aplicación automática requiere el planificador corriendo.** Si el cron no está configurado en
   el servidor, las políticas no se aplicarán solas; el comando manual sigue disponible y el `--dry-run`
   permite verificar que la configuración es la esperada.

---

## Cómo verificar todo

```bash
# Todas las pruebas del RNF-09
php artisan test --filter=Rnf09

# Solo el cifrado
php artisan test --filter=Rnf0901DuiNitEncryptionTest

# Retención y su registro
php artisan test --filter=Rnf09RetentionTest

# Separación de acceso
php artisan test --filter=Rnf0903AccessSeparationTest

# Ver qué haría la retención, sin modificar nada
php artisan retention:apply --dry-run

# Ver el histórico de operaciones aplicadas
php artisan tinker --execute="App\Models\RetentionLog::latest()->take(10)->get(['categoria','accion','registros_afectados','resultado','created_at'])"
```
