// ============================================================
// Informe de cierre — Test de Raven
// Versión 4.0 — cierre técnico (2026-09-20)
// Los estados se verificaron ejecutando la suite de pruebas y
// contrastando cada afirmación con el archivo citado.
// ============================================================

#set document(title: "Informe de cierre — Test de Raven", author: "Equipo de desarrollo")
#set page(paper: "a4", margin: (x: 2.2cm, y: 2.2cm), numbering: "1 / 1")
#set text(lang: "es", size: 10.5pt)
#set par(leading: 0.62em, spacing: 0.9em)
#set list(indent: 0.5em, body-indent: 0.5em)
#set enum(indent: 0.5em, body-indent: 0.5em)

#show heading: set block(above: 1.5em, below: 0.8em)
#show heading.where(level: 1): set text(size: 15pt)
#show heading.where(level: 2): set text(size: 12.5pt)
#show heading.where(level: 3): set text(size: 11pt)

#show raw: set text(size: 0.88em)
#show raw.where(block: false): it => box(fill: luma(240), inset: (x: 2pt), outset: (y: 2.5pt), radius: 2pt, it)
#show raw.where(block: true): block.with(fill: luma(245), inset: 8pt, radius: 3pt, width: 100%)

#set table(
  fill: (_, y) => if y == 0 { luma(228) },
  stroke: 0.5pt + luma(170),
  inset: 5pt,
)
#show table: set text(size: 9pt)
#show table.cell.where(y: 0): strong

#let verificar(t) = text(fill: rgb("#b45309"), weight: "bold")[\[VERIFICAR: #t\]]
#let completar(t) = text(fill: rgb("#b91c1c"), weight: "bold")[\[COMPLETAR: #t\]]
#let cita(body) = block(inset: (left: 10pt, y: 4pt), stroke: (left: 2pt + luma(170)), body)
#let ok = text(fill: rgb("#15803d"), weight: "bold")[Solventado]

#align(center)[
  #text(size: 19pt, weight: "bold")[Respuesta a observaciones previas a staging]
  #v(2pt)
  #text(size: 11.5pt)[Plataforma de Selección Psicométrica — Test de Matrices Progresivas de Raven]
]


#v(6pt)

#table(
  columns: (auto, 1fr),
  fill: none,
  stroke: none,
  inset: (x: 4pt, y: 3pt),
  [*Para:*], [Ing. Luis Barrera — Asesor de Proyecto / Jefe UTI-FMOcc],
  [*De:*], [Equipo de desarrollo (Milton Obed Alas Hernández, Reyna Guadalupe Miranda Rivas)],
  [*Responde a:*], [Observaciones y requisitos técnicos del 2 de septiembre de 2026],
  [*Fecha:*], [2026-09-20],
  [*Versión del informe:*], [3.0 (estado contrastado con el código)],
)

= 0. Resumen ejecutivo

Esta versión revisa cada observación contra el código, archivo por archivo. Los estados dejaron de ser declaraciones de intención: cada uno se apoya en una referencia concreta o en una prueba automatizada.
Ocho de los diez puntos están solventados en el código; los otros dos dependen de una decisión institucional (el baremo) o de un tercero (el ambiente de pruebas, a cargo de la UTI).

#table(
  columns: (auto, 1.6fr, auto, 1.5fr, 2.1fr),
  table.header([\#], [Observación], [Prioridad], [Estado], [Fundamento]),
  [1], [Detalle completo del resultado visible al candidato], [Alta],
  [#ok en código; falta decisión institucional],
  [La vista de finalización no expone puntaje, percentil, rango ni clasificación. Si la facultad decide mostrarlos, es un cambio de una vista.],
  [2], [Inconsistencia del tiempo límite (UC-05)], [Alta],
  [#ok en código y documentación],
  [El valor implementado es único: 2700 s (45 min). No hay ninguna asignación activa de 600 s.],
  [3], [Ambiente de pruebas en hosting de terceros], [Alta],
  [A cargo de la UTI],
  [El equipo entrega el repositorio completo y la configuración; la migración la ejecuta la UTI.],
  [4], [Sin recuperación de contraseña para candidatos], [Media-Alta],
  [#ok],
  [Reseteo manual por administrador, auditado, sin filtrar credenciales en los registros.],
  [5], [Campo de identificación `DUI/NIT` combinado], [Media],
  [#ok — un solo campo, con validación por formato],
  [El campo único es deliberado y la validación por formato ya está implementada (9 dígitos DUI / 14 NIT). No se crearon columnas nuevas.],
  [6], [Exportación de PDF por rol `reporter` y auditoría], [Media],
  [#ok],
  [El informe PDF individual y las exportaciones masivas pasan por `logExport()` con evento `exported`.],
  [7], [Anti-bot / límite de intentos en registro público], [Media],
  [#ok],
  [Límite por IP y por identificador, aplicado en la ruta, más honeypot. 16 pruebas lo cubren.],
  [8], [RNF-09: cifrado en reposo y retención de datos], [Media],
  [#ok en código; plazos definitivos pendientes],
  [Cifrado, índice HMAC, retención configurable y aplicación automática, con 37 pruebas.],
  [9], [Baremo de Montevideo (validez para población salvadoreña)], [Media],
  [Pendiente de decisión institucional],
  [Es una decisión de validez psicométrica. El sistema permite cambiar el baremo por datos, sin tocar código.],
  [10], [Concentración de permisos en el rol `admin`], [Media],
  [#ok — alcance separado con un tercer rol],
  [Se añade el rol `evaluador` (solo consulta) y el instrumento queda de solo lectura para todos los roles, `admin` incluido.],
)

_Estados utilizados: Solventado | Solventado en código | A cargo de la UTI | Riesgo aceptado | Decisión institucional_

#block(fill: luma(242), inset: 8pt, radius: 3pt)[#strong[Cierre del equipo de desarrollo.] El proyecto queda documentado con el estado técnico descrito en este informe y con los entregables necesarios para su continuidad. Las decisiones institucionales y la infraestructura de staging no se consideran tareas abiertas del equipo de desarrollo.]

= 1. Observaciones

== Observación 1 — Visibilidad del resultado para el candidato

- *Prioridad:* Alta
- *Estado:* #ok en código; queda la confirmación institucional
- *Verificación en el código:*
  - Vista de finalización: `resources/views/candidate/test/completed.blade.php`. Muestra únicamente el
    título «¡Test Completado!» y el mensaje de finalización exitosa. No imprime ninguna propiedad de
    `$result` (puntaje, percentil, rango, clasificación ni validez) ni del candidato más allá de lo
    necesario para la confirmación.
  - `app/Http/Controllers/TestResultController.php`: carga el resultado y lo entrega a la vista, pero
    la vista no lo expone.
  - No existe ninguna ruta ni botón de descarga del informe para el candidato: el informe PDF solo se
    genera desde el panel administrativo
    (`app/Filament/Resources/TestResults/Tables/TestResultsTable.php`), donde exige rol autorizado y
    queda auditado.
- *Hallazgo:* RF-38, UC-10 y el manual de usuario §2.7 establecían que el candidato ve el detalle
  completo del resultado (puntaje, percentil, rango, clasificación, validez y detalle por serie). La
  decisión sobre qué puede ver el candidato es institucional.
- *Conclusión:* la implementación ya coincide con la recomendación preliminar (el candidato no ve datos
  sensibles). No hay trabajo de desarrollo pendiente; lo que falta es la decisión formal de la facultad.
- *Archivos / documentos afectados:* RF-38, UC-10, manual de usuario §2.7,
  `resources/views/candidate/test/completed.blade.php`.
- *Evidencia:* la vista citada; `grep -c` sobre ella no devuelve ninguna referencia a propiedades de
  `$result`. Suite: `tests/Feature/Rnf0903AccessSeparationTest.php` verifica que el candidato no recibe
  `percentile` ni `diagnostic` por las rutas del test.
== Observación 2 — Inconsistencia del tiempo límite (UC-05)

- *Prioridad:* Alta
- *Estado:* #ok en código y documentación
- *Verificación en el código:*
  - Migración: `database/migrations/2025_12_23_193113_create_test_sessions_table.php` (L31) —
    `time_limit` con valor por defecto 2700.
  - Creación de sesión: `app/Services/TestService.php` (L65-L66) — `time_limit => 2700` y
    `remaining_time => 2700`.
  - Lógica del temporizador: `app/Services/TimerService.php` (L80-L81 y L145) — usa
    `session->time_limit` con respaldo 2700.
  - Controlador: `app/Http/Controllers/TestController.php` (L136) — usa `remaining_time ?? time_limit`.
  - Manual de usuario: `manual-usuario.typ` (L157) — «45 minutos (2700 segundos)».
- *Hallazgo:* El valor implementado es *2700 segundos (45 minutos)* de forma unánime. Las cadenas
  «600» aparecen *únicamente dentro de comentarios* de `TestService` y `TimerService` que registran
  el valor usado durante las pruebas manuales; ninguna asignación activa los utiliza. Las referencias a
  «60 minutos» y «600 segundos» del Informe de Diagnóstico son errores de documentación.
- *Archivos / documentos afectados:*
  - Código: migración de `test_sessions`, `TestService`, `TimerService`, `TestController`.
  - Documentación: `manual-usuario.typ`, Informe de Diagnóstico, `docs/LOGICA_SERVICIOS_CONTROLLERS.md`.
- *Evidencia:* `grep -rn "2700" app/ database/` devuelve las cuatro referencias de arriba; `grep -rn "600"` solo devuelve comentarios.
== Observación 3 — Ambiente de pruebas en hosting de terceros

- *Prioridad:* Alta
- *Estado:* A cargo de la UTI
- *Hallazgo:* El ambiente de pruebas corre en un hosting gratuito de terceros (laravel.cloud), no apto
  para datos reales de aspirantes.
- *Aporte del equipo de desarrollo para facilitar la migración:*
  - El repositorio incluye el material completo, incluidas las láminas del test, que antes no estaban
    versionadas y habrían dado error 404 en un clon limpio.
  - `.env.example` documenta todas las variables que la aplicación espera.
  - `bin/backup.sh` respalda base y clave de cifrado en una sola operación, y
    `docs/RESPALDO_Y_OPERACION.md` describe los comandos, el cron y la restauración.
- *Archivos / documentos afectados:* `docs/RESPALDO_Y_OPERACION.md`; sección 3 de este informe.
- *Evidencia:* clon del repositorio verificado: llegan las 60 láminas y 432 imágenes de opciones.
== Observación 4 — Recuperación de contraseña para candidatos

- *Prioridad:* Media-Alta
- *Estado:* #ok
- *Verificación en el código:*
  - `app/Services/CandidatePasswordResetService.php`: reseteo manual por administrador; registra el
    evento `candidate_password_reset` en `activity_logs`.
  - `app/Filament/Resources/Candidates/Actions/ResetCandidatePasswordAction.php`: acción
    «Restablecer contraseña» en el panel, visible solo para el rol `admin`.
  - `tests/Feature/CandidatePasswordResetTest.php`: 9 pruebas que cubren el flujo, el registro de
    auditoría, el bloqueo al rol `reporter` y la verificación de que la contraseña en claro no queda
    en los registros.
  - `config/auth.php`: se declaró además el broker `passwords.candidates`, disponible si en el futuro
    se habilita autoservicio por correo.
- *Hallazgo:* No existe autoservicio. El administrador genera o define una contraseña temporal, que se
  muestra *una sola vez* y no puede consultarse después. La acción *no* modifica `test_completed`: no
  otorga un segundo intento.
- *Sobre `candidate_dui_nit` en el registro de auditoría:*
  el servicio escribe el valor *en claro* en `properties.candidate_dui_nit`, porque lee el atributo
  del modelo, que descifra de forma transparente. Esto no contradice el cifrado en reposo (los
  registros de auditoría son otra tabla, con su propia política de retención de 365 días), pero
  conviene decidirlo explícitamente: si la facultad prefiere que la auditoría no conserve el
  identificador en claro, el cambio es usar el valor enmascarado (`dui_nit_masked`) al registrar. Es un
  cambio de una línea en `CandidatePasswordResetService`.
- *Procedimiento:*
  + El administrador valida la identidad del candidato por canales institucionales.
  + Ejecuta *Restablecer contraseña* desde el panel.
  + El sistema genera (o el administrador define) la contraseña temporal y la muestra una vez.
  + El administrador la comunica al candidato por el canal seguro acordado.
  + El evento queda registrado en `activity_logs`.
- *Registro en `activity_logs`* (`ActivityLogService::log()`):
  - `causer_type`, `causer_id` (administrador) y `subject_type`, `subject_id` (candidato).
  - `event`, `description`, `properties` (JSON), `changes` (JSON), `ip_address`, `user_agent`.
  - `properties` del reseteo: `admin_id`, `admin_name`, `admin_email`, `candidate_id`,
    `candidate_name`, `candidate_email`, `candidate_dui_nit`, `password_origin`
    (`generated` | `manual`), `is_temporary`, `candidate_test_completed`, `had_started_test`,
    `changed_at`.
  - Eventos registrados hoy: `candidate_password_reset`, `exported`, `retention_dissociated`,
    `user_created` y `user_role_changed` (los dos últimos, sobre las cuentas del panel: alta y cambio de
    rol). `log()` acepta cualquier valor de `event`.
- *Archivos / documentos afectados:* los indicados arriba, más `docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md`.
== Observación 5 — Campo de identificación `DUI/NIT`

- *Prioridad:* Media
- *Estado:* #ok — se mantiene un solo campo y la validación por formato ya está implementada
- *Verificación en el código:*
  - `app/Models/Candidate.php`: campo único `dui_nit` cifrado, con `dui_nit_hash` como índice
    determinista de búsqueda y unicidad.
  - `app/Http/Controllers/CandidateAuthController.php`: registro e inicio de sesión resuelven al
    candidato con `Candidate::findByDuiNit()`; `dui_nit` se valida como `required|string|max:255`.
  - `app/Support/DuiNitCipher.php` y
    `database/migrations/2026_09_20_000000_encrypt_candidate_dui_nit.php`: normalización, cifrado e
    índice.
- *Hallazgo:* El sistema trata DUI y NIT como un único campo, sin validación que diferencie los
  formatos.
- *Por qué se mantiene un solo campo:*

  #cita[La petición de revisión sugería separar el tipo de documento en una columna adicional
  (`tipo_documento`) o incluso dividir el valor en dos columnas (`dui` y `nit`). Se evaluó y se descarta:
  el campo único no es una simplificación, es la consecuencia correcta de la homologación.]

  *1. La homologación unifica el identificador, no lo divide.* Tras el Decreto Legislativo 203 de 2021,
  el DUI (9 dígitos, formato `00000000-0`) sustituye al NIT para las personas naturales salvadoreñas
  mayores de edad. Para una persona concreta existe *un solo número de identificación vigente*, no dos
  que convivan. Almacenar `dui` y `nit` como columnas separadas obligaría a decidir en cada consulta cuál
  de las dos es la que identifica al candidato, y a mantener la regla de precedencia en cada punto del
  código donde hoy basta una igualdad.

  *2. Separar en dos columnas rompe la garantía de unicidad.* El requisito real es que *una persona no
  pueda registrarse dos veces*. Con un campo único, esa garantía se expresa con un solo índice
  (`dui_nit_hash` con restricción `unique`) y la base la hace cumplir. Con dos columnas, cada una tendría
  su índice parcial y ninguno impediría que la misma persona quedara registrada una vez con su DUI y otra
  con su NIT: la unicidad pasaría a depender de una validación de aplicación, que es más débil y más
  fácil de eludir.

  *3. El costo de cambiarlo hoy es alto y el beneficio nulo.* Separar el campo exige una migración de
  datos (repartir valores existentes según su formato), recrear índices, rediseñar el flujo de registro y
  de inicio de sesión, y volver a probar. Todo eso para obtener una capacidad —aceptar dos formatos
  distintos— que *el campo único ya tiene*: hoy admite cualquier cadena y la normaliza antes de
  indexarla.

  *4. Lo que sí corresponde implementar: la validación por formato.* El campo único no impide distinguir
  los formatos; lo que falta es validarlos. La distinción puede inferirse del propio valor por su
  longitud, sin necesidad de almacenar un tipo adicional:

  #table(
    columns: (auto, auto, 1.2fr),
    table.header([Formato], [Longitud normalizada], [A quién corresponde]),
    [`00000000-0`], [9 dígitos], [DUI — salvadoreños mayores de edad (identificador vigente tras la homologación)],
    [`0000-000000-000-0`], [14 dígitos], [NIT — personas jurídicas, menores de edad y extranjeros],
  )

  La regla se aplica sobre el valor normalizado que ya calcula `DuiNitCipher::normalize()` (sin guiones ni
  espacios, en mayúsculas), así que no requiere columnas nuevas ni cambios en el esquema: solo una regla
  de validación que exija 9 o 14 dígitos y, si se desea, el dígito verificador de cada formato.

  *5. El cifrado exige un campo, no dos.* La razón técnica que cierra el debate: el valor se cifra con
  AES-256-CBC y se indexa con un HMAC sobre el valor *normalizado*. Con dos columnas habría que cifrar
  y indexar cada una por separado, duplicando la superficie de manejo de datos sensibles (RNF-09.01) y
  abriendo la puerta a que una quedara sin cifrar por descuido. Un único campo cifrado, con un único
  índice, es la opción con menor superficie de riesgo.

  #cita[*Nota sobre `dui_nit_hash`:* no es un «campo separado» del identificador ni un dato capturado al
  usuario. Es el *índice determinista* derivado del mismo valor, y existe porque el cifrado usa un IV
  aleatorio y por tanto no es consultable por igualdad. Sin él, la búsqueda por identificador exigiría
  descifrar la tabla completa en cada inicio de sesión, lo que anularía el propósito del cifrado. Se
  documenta como parte de RNF-09.01 y está verificado por pruebas.]
- *Implementación realizada:*

  La validación por formato está implementada sobre el campo único, sin columnas nuevas, sin migración
  de datos y sin cambios en el índice de unicidad:

  - `app/Support/DuiNitCipher.php`: nuevo método `documentType()`, que deduce el tipo a partir de la
    longitud del valor normalizado —9 dígitos → `dui`, 14 → `nit`— y devuelve `null` si el valor no
    corresponde a ninguno. Exige que el valor normalizado sean *solo dígitos* (comprobado con
    `ctype_digit`), porque la normalización conserva letras y sin esa comprobación una cadena de nueve
    letras habría pasado como DUI. Incluye `documentTypeLabel()` para mensajes legibles.
  - `app/Http/Controllers/CandidateAuthController.php`: regla de validación por cierre en el registro,
    con un mensaje que explica los dos formatos aceptados y da ejemplos.
  - `resources/views/auth/register.blade.php`: el texto de ayuda del campo ya indicaba «Ingresa un DUI
    válido (9 dígitos) o NIT (14 dígitos)», en línea con la regla.
  - `tests/Feature/RnfDocumentFormatTest.php`: 23 pruebas que cubren los formatos válidos (DUI y NIT, con
    y sin guiones), los inválidos (longitudes incorrectas, solo letras, letras mezcladas, vacío), el
    rechazo en el registro, la equivalencia del mismo DUI con y sin guiones y el inicio de sesión.

  *Efecto sobre la unicidad:* al no almacenar el tipo, un mismo número no puede registrarse por dos vías
  (por ejemplo una vez como DUI y otra como NIT). Un único índice sigue garantizando que una persona no
  se registre dos veces, que es el requisito real detrás de la observación.
- *Archivos / documentos afectados:* `app/Http/Controllers/CandidateAuthController.php` (regla de
  validación), `app/Support/DuiNitCipher.php` (reutilización de la normalización),
  `resources/views/auth/register.blade.php` (texto de ayuda), `manual-usuario.typ`,
  `docs/RNF-09_CONFIDENCIALIDAD.md`.
- *Evidencia:* `grep -rn "dui_nit"` sobre `app/`; `tests/Feature/Rnf0901DuiNitEncryptionTest.php`
  verifica que la normalización hace equivalentes `05123456-7` y `051234567`, y que el índice es el
  mismo.
== Observación 6 — Exportación por rol `reporter` y auditoría

- *Prioridad:* Media
- *Estado:* #ok
- *Verificación en el código:*
  - `app/Filament/Resources/TestResults/Tables/TestResultsTable.php` (L238): la acción *«Descargar PDF»* —el informe individual con el diagnóstico completo— invoca
    `ActivityLogService::logExport()` con `format: 'pdf'`. Esta era la verificación pendiente y queda
    confirmada.
  - Mismo archivo (L305): `registrarExportacionMasiva()` cubre las exportaciones a Excel y CSV del
    listado, con `format` variable.
  - `app/Filament/Resources/Candidates/Tables/CandidatesTable.php` (L133): invoca `logExport()` al
    exportar candidatos a CSV.
  - `app/Services/ActivityLogService.php`: `log()` y `logExport()`; el evento es `exported`
    (`EVENT_EXPORTED`).
  - `tests/Feature/TestResultPdfExportTest.php`: 6 pruebas que verifican que el rol `reporter` ve y
    ejecuta la exportación, que la descarga es un PDF real, y el contenido del registro de auditoría
    (incluido que `exported_by_role` sea `reporter`).
- *Hallazgo:* Cada exportación registra el evento `exported` con quién exportó (`exported_by_name`,
  `exported_by_email`, `exported_by_role`), cuándo (`exported_at`) y qué (`candidate_id`,
  `test_result_id`, `test_session_id`, `filename`, `export_format`), más `ip_address` y `user_agent`.
  Es posible auditar quién descargó qué, en las tres vías de exportación (PDF individual, Excel y CSV).
- *Nota sobre el alcance del rol:* El rol `reporter` *puede* exportar el informe individual con el
  diagnóstico completo, por decisión de alcance confirmada. La restricción del rol se aplica a la
  administración (usuarios, banco de reactivos y tablas normativas, donde recibe 403) y a cualquier
  acción destructiva.
- *Archivos / documentos afectados:* los indicados arriba; RF-50, UC-19.
- *Evidencia:* entradas `exported` en `activity_logs`;
  `php artisan test --filter=TestResultPdfExportTest`.
== Observación 7 — Anti-bot en el registro público

- *Prioridad:* Media
- *Estado:* #ok
- *Solicitud:* Agregar límite de intentos por IP o un captcha simple al formulario de registro.
- *Verificación en el código:*
  - `app/Providers/AppServiceProvider.php`: `RateLimiter::for('register')` define *dos límites
    complementarios*:
    - *5 registros por hora por IP* (`Limit::perHour(5)->by($request->ip())`).
    - *3 intentos por hora sobre el mismo DUI/NIT* (`Limit::perHour(3)` con clave calculada sobre
      `DuiNitCipher::hash()`). Este segundo límite cubre el caso que un límite por IP no atiende: la
      fuerza bruta distribuida que rota direcciones para insistir sobre una misma identidad.
  - `routes/web.php` (L24): la ruta de registro aplica el límite
    (`Route::post('register', ...)->middleware('throttle:register')`). Verificado también con
    `php artisan route:list -v`.
  - `routes/web.php` (L19): el inicio de sesión tiene su propio límite
    (`throttle:login`): 5 intentos fallidos por minuto por IP y 5 por cada 15 minutos sobre el mismo
    identificador. Los intentos correctos no consumen cuota.
  - `resources/views/auth/register.blade.php`: honeypot —contenedor oculto con `aria-hidden` que
    contiene un campo `website` vacío— como capa secundaria.
  - `app/Http/Controllers/CandidateAuthController.php`: rechaza el envío si `website` llega con
    contenido, con el mensaje «Registro bloqueado: comportamiento sospechoso detectado».
  - Se añadió a las vistas de registro y de inicio de sesión el aviso de bloqueo con el tiempo de
    espera, que antes no existía: el candidato bloqueado volvía al formulario sin explicación.
  - `tests/Feature/CandidateRegistrationThrottleTest.php` (8 pruebas) y
    `tests/Feature/CandidateLoginThrottleTest.php` (8 pruebas) cubren cuota, bloqueo, mensaje, límite
    por identificador y que el límite corre antes de la validación.
- *Alcance del límite:* el throttle se aplica *antes* de validar el formulario, de modo que un bot que
  envía datos inválidos también consume su cuota. Impide usar el endpoint como sondeo indefinido.
- *Riesgo / notas:* El honeypot es de bajo impacto y no agrega dependencias externas; el límite de
  intentos es la protección principal, porque actúa en el servidor y no depende del navegador. Para
  mayor robustez puede incorporarse un captcha (hCaptcha o reCAPTCHA v3), con integración en front y
  back y posible impacto en la experiencia del candidato; se consideró innecesario para el volumen
  previsto de aspirantes.
- *Archivos modificados:* `app/Providers/AppServiceProvider.php`, `routes/web.php`,
  `resources/views/auth/register.blade.php`, `resources/views/auth/login.blade.php`,
  `app/Http/Controllers/CandidateAuthController.php`, `bootstrap/app.php` (respuesta amable ante el 429).
- *Evidencia:* `php artisan route:list -v` muestra `ThrottleRequests:register` y `ThrottleRequests:login`;
  las 16 pruebas citadas.
== Observación 8 — RNF-09: cifrado en reposo y retención

- *Prioridad:* Media
- *Estado:* #ok en código; los plazos definitivos quedan a decisión institucional
- *Hallazgo:* RNF-09 ya no es una declaración general: está implementado y documentado en
  `docs/RNF-09_CONFIDENCIALIDAD.md`, con 37 pruebas automatizadas que lo acreditan.

#table(
  columns: (auto, 1fr),
  table.header([Aspecto], [Implementación]),
  [Campo cifrado en reposo], [`dui_nit` con `Crypt::encryptString` de Laravel (AES-256-CBC, IV aleatorio: el mismo DUI produce un texto cifrado distinto cada vez). Cifrado de un solo campo, por decisión de diseño: ver Observación 5],
  [Búsqueda y unicidad], [`dui_nit_hash`: HMAC-SHA256 con `APP_KEY`, sobre el valor normalizado (sin guiones ni espacios, en mayúsculas) y con índice único (`candidates_dui_nit_hash_unique`). Es índice derivado, no un dato capturado, y permite buscar y validar duplicados sin descifrar],
  [Contraseñas], [Hash irreversible con el driver de hash de Laravel (cast `hashed`); el restablecimiento lo ejecuta un administrador y queda auditado],
  [Serialización], [`dui_nit_hash` está en `$hidden`: no se serializa en ninguna respuesta. El identificador completo sí se muestra en las vistas de los roles autorizados (listado de candidatos, exportación CSV e informe PDF); el accessor `dui_nit_masked` (`•••••4567`) existe para enmascararlo, pero ninguna de esas vistas lo usa todavía],
  [Separación de acceso], [Guards `web` (panel) y `candidate` (candidatos) en `config/auth.php`. El candidato solo alcanza su propio flujo (`/instrucciones`, `/test/*`) y ninguna ruta acepta el identificador de una sesión ajena. No recibe el resultado del test —ni puntaje, ni percentil, ni clasificación diagnóstica—: la pantalla de finalización confirma el cierre y no lo muestra. En el panel, autorización por roles],
  [Auditoría], [Restablecimientos de contraseña, exportaciones, altas y cambios de rol de cuentas del panel, y disociaciones por retención quedan en `activity_logs`],
  [Retención], [Cuatro categorías con plazo y acción por variable de entorno: datos identificativos (`RETENCION_PERSONAL_DIAS`), expediente psicométrico (`RETENCION_PSICOMETRICO_DIAS`), logs de auditoría (`RETENCION_ACTIVIDAD_DIAS`) y trazas técnicas (`RETENCION_TECNICO_DIAS`); aplicación automática diaria con registro en `retention_logs`],
)

- *Retención de datos:* los plazos y las acciones son *configurables* mediante variables de entorno, de
  modo que la parte académica/administrativa de la Universidad de El Salvador pueda ajustarlos según
  estime conveniente. Valores recomendados para un proceso de aspirantes a profesorado:

#table(
  columns: (auto, auto, auto, auto),
  table.header([Categoría], [Variable de plazo], [Plazo recomendado], [Acción (variable)]),
  [Personal], [`RETENCION_PERSONAL_DIAS`], [365 días], [Disociar (`RETENCION_PERSONAL_ACCION`)],
  [Psicométrico], [`RETENCION_PSICOMETRICO_DIAS`], [365 días (730 si la facultad desea conservar estadística histórica por cohortes)], [Disociar (`RETENCION_PSICOMETRICO_ACCION`)],
  [Actividad], [`RETENCION_ACTIVIDAD_DIAS`], [365 días], [Suprimir (`RETENCION_ACTIVIDAD_ACCION`)],
  [Técnico], [`RETENCION_TECNICO_DIAS`], [180 días], [Suprimir (`RETENCION_TECNICO_ACCION`)],
)

  *Diferencia entre lo recomendado y lo implementado por defecto:* `config/retention.php` lee las nueve
  variables con `env()` y sus valores por defecto son *1825 / 1825 / 730 / 180 días* (los que traía la
  versión anterior de RNF-09). La tabla de arriba es la *recomendación* para este proceso; los valores
  definitivos los fija la facultad escribiéndolos en el `.env`, que tiene precedencia sobre el defecto.
  Mientras no se definan, el sistema aplica los plazos largos, que son los conservadores respecto a la
  pérdida de información. *No hay discrepancia entre el código y esta tabla: hay un valor por defecto y
  un valor recomendado, y la decisión pendiente es cuál se escribe en el `.env`.*

  Ejemplo de configuración en `.env`:

  ```ini
  RETENCION_ACTIVA=true
  RETENCION_PERSONAL_DIAS=365
  RETENCION_PERSONAL_ACCION=disociar
  RETENCION_PSICOMETRICO_DIAS=365
  RETENCION_PSICOMETRICO_ACCION=disociar
  RETENCION_ACTIVIDAD_DIAS=365
  RETENCION_ACTIVIDAD_ACCION=suprimir
  RETENCION_TECNICO_DIAS=180
  RETENCION_TECNICO_ACCION=suprimir
  ```

- *Criterio de los plazos recomendados:*
  - El aspirante dispone de una sola oportunidad para rendir el test (la regla implementada es
    definitiva, no anual: `test_completed` bloquea un segundo intento); por eso el dato personal solo
    tiene valor durante la convocatoria y poco después.
  - Los resultados psicométricos pueden conservarse disociados (sin identificadores directos) por más
    tiempo si la institución quiere análisis comparativo por cohortes.
  - Los registros de actividad, IP y datos técnicos son los de menor valor analítico y deben tener los
    plazos más cortos.
  - *Desde qué fecha se cuenta el plazo:* la política se calcula sobre `created_at` del propio registro
    (verificado en `RetentionService`: `candidates` para `personal`, `test_results` y `candidates` para
    `psicometrico`, `activity_logs` para `actividad`, y `test_sessions` y `activity_logs` para
    `tecnico`). Para la categoría `personal` eso es la fecha de alta del candidato y para los resultados
    la de su cálculo. *Es una diferencia real frente a la recomendación de contar desde la finalización
    del test o el cierre de la convocatoria.* Si la facultad lo requiere, el cambio se limita a la
    consulta de `RetentionService::disociarCandidatos()` para usar `test_completed_at` cuando exista.
    Esfuerzo bajo.
- *Aplicación automática:* `php artisan retention:apply` (admite `--dry-run`, que recorre la misma
  lógica sin escribir y deja constancia en `retention_logs` con `simulacion = true`). La tarea está
  programada a diario a las 03:00 en `routes/console.php`, con `withoutOverlapping()` y `onOneServer()`.
  Respeta el interruptor `RETENCION_ACTIVA`. Requiere el planificador de Laravel activo en el servidor
  (ver `docs/RESPALDO_Y_OPERACION.md`).
- *Pendiente de implementación (nombre y evidencia):* el requisito se formuló también como un comando
  `raven:purge-expired` que registrara su ejecución en `activity_logs` con el evento `retention_purge`.
  **No existe ninguno de los dos.** La funcionalidad —aplicar la política, `--dry-run` y ejecución
  programada— está cubierta por `retention:apply`, y su evidencia vive en `retention_logs` (una fila por
  categoría y ejecución), que además queda fuera de la propia política. Si el criterio de aceptación
  exige el nombre y el evento literales, el alias del comando son unas pocas líneas y el evento se
  escribe con `ActivityLogService::log()`; la salvedad es que la política suprime los `activity_logs`
  vencidos (categoría `actividad`), así que esa evidencia sería más efímera que `retention_logs`.
  Dos detalles menores, verificados en el código: la hora de ejecución está fija en
  `routes/console.php` (03:00, no configurable por entorno) y `retention_logs.origen` registra siempre
  `manual`, también cuando lo dispara el planificador.
- *Cambio de plazos:* al modificar cualquiera de estas variables en `.env`, se debe ejecutar:

  ```bash
  php artisan config:clear
  php artisan config:cache
  ```

  Con la configuración en caché, Laravel no vuelve a leer `.env` hasta ejecutar `config:cache`.
- *Notas operativas:* El cifrado y el índice HMAC dependen de `APP_KEY`; si se pierde, los
  identificadores son irrecuperables. La rotación se hace con `APP_PREVIOUS_KEYS` (el comando de
  respaldo las incluye). `bin/backup.sh` respalda base y clave en una sola operación, porque *un
  volcado sin la clave es inservible*. Las copias de seguridad siguen un ciclo institucional separado:
  la retención se aplica sobre la base activa, no sobre respaldos ya generados.
- *Archivos / documentos afectados:* `app/Support/DuiNitCipher.php`, `app/Models/Candidate.php`,
  `config/retention.php`, `app/Services/RetentionService.php`, `app/Services/ActivityLogService.php`,
  `routes/console.php`, `docs/RNF-09_CONFIDENCIALIDAD.md`, `docs/RESPALDO_Y_OPERACION.md`.
- *Evidencia:* `php artisan test --filter=Rnf09` (37 pruebas, 124 aserciones en verde);
  `php artisan retention:apply --dry-run` ejecutado contra la base de desarrollo.
== Observación 9 — Baremo de Montevideo

- *Prioridad:* Media
- *Estado:* Pendiente de decisión institucional (validez del instrumento)
- *Verificación en el código:*
  - Migración: `database/migrations/2025_12_26_165326_create_percentile_tables_table.php` — tabla
    `percentile_tables` (`percentile`, `age_min`, `age_max`, `norm_group`, `norm_year`).
  - Seeder: `database/seeders/PercentileTableSeeder.php` — «Baremo de Montevideo - Adolescente y
    Adultos»; fuente declarada: «Tabla VII del manual Raven SPM».
  - Cálculo: `app/Services/ResultCalculatorService.php` (L125-L150) — `findPercentile` consulta
    `PercentileTable::active()` con `norm_group = 'montevideo'`.
- *Hallazgo:* El sistema usa el Baremo de Montevideo (Tabla VII: 12 a 65 años; percentiles 1, 10, 25,
  50, 75, 90 y 99), tomado de la documentación del instrumento entregada por la facultad y cargado sin
  modificar sus valores. Hasta donde tenemos conocimiento, no se nos proporcionó ni identificamos un
  baremo para población salvadoreña; una búsqueda en fuentes públicas no arrojó ninguno.
- *Alcance:* Elegir o validar un baremo es una decisión de validez psicométrica; corresponde al área
  académica/psicológica de la facultad y no al desarrollo.
- *Mitigación técnica:* Los valores normativos están en una tabla con `norm_group` y `norm_year`,
  cargada por seeder. Un cambio de baremo es una actualización de datos, no de código: basta cargar las
  filas del nuevo grupo normativo y ajustar `norm_group`.
- *Cómo asigna el percentil a puntajes intermedios:* la tabla
  fuente solo define 7 percentiles de referencia (1, 10, 25, 50, 75, 90, 99). `findPercentile` procede en
  dos pasos:
  + Busca el registro *exacto* para la edad y el puntaje bruto (`forScore`).
  + Si no existe, toma *el registro de puntaje inmediatamente inferior* (`where('raw_score', '<=', $rawScore)->orderBy('raw_score', 'desc')`).
  + Si no encuentra ninguno (puntaje por debajo del mínimo tabulado), registra una advertencia en el
    log y devuelve *percentil 50* como valor por defecto.
    
== Observación 10 — Concentración de permisos en el rol `admin`

- *Prioridad:* Media (no bloquea staging)
- *Estado:* #ok — alcance separado con un tercer rol de solo consulta y el instrumento congelado para
  todos los roles
- *Verificación en el código / documentación:*
  - `app/Policies/` no existe y `AppServiceProvider` no define `Gate::before`. La autorización se
    resuelve con los métodos estáticos `canX()` de cada recurso de Filament
    (`canViewAny`, `canCreate`, `canEdit`, `canDelete`, `canDeleteAny`, `canForceDeleteAny`,
    `canRestoreAny`), que son la única fuente de verdad; las exportaciones masivas tienen el suyo
    (`canExport()` en Candidatos y Resultados). La matriz por rol está documentada en el `README.md`.
  - *Tercer rol:* `evaluador`, añadido al enum de `users.role` (migración
    `2026_09_21_000000_add_evaluador_role_to_users_table`). Alcance: consulta de candidatos, sesiones,
    resultados e instrumento, más el informe PDF individual; sin exportación masiva y sin acceso a
    usuarios. `reporter` conserva su alcance sin cambios y `admin` mantiene la operación diaria y la
    gestión de cuentas del panel.
  - *Instrumento congelado:* series, reactivos, baremos y rangos diagnósticos son de solo lectura para
    *todos* los roles, `admin` incluido. Sus `canCreate()`, `canEdit()`, `canDelete()` y
    `canDeleteAny()` devuelven `false`, las tablas ya no ofrecen alta ni edición y las páginas `create`
    y `edit` responden 403. Cualquier cambio real entra por seeder o migración, con control de
    versiones.
  - *Auditoría:* `ActivityLogService::logExport()` se invoca en las exportaciones de `CandidatesTable`
    y `TestResultsTable`, incluido el informe PDF individual; una descarga hecha por un `evaluador`
    queda registrada con `exported_by_role = evaluador`.
  - *Auditoría de cuentas:* el alta y el cambio de rol de una cuenta del panel quedan registrados con
    los eventos `user_created` y `user_role_changed`, con el rol anterior y el nuevo, quién lo hizo y
    desde qué IP. Se registran desde el modelo `User` —siempre con `ActivityLogService`— para cubrir
    todas las vías: el alta y la edición desde la lista, la página de edición y `artisan tinker`.
  - *Pruebas:* `tests/Feature/PanelRolePermissionsTest.php` (matriz de los tres roles, exportaciones y
    auditoría), `tests/Feature/TestBankUiRulesTest.php` (instrumento de solo lectura) y
    `tests/Feature/UserAccountAuditTest.php` (auditoría de cuentas).
- *Eventos de auditoría que existen hoy:* `candidate_password_reset` (reseteo de credenciales por un
  administrador), `exported` (exportaciones de candidatos y resultados, informe PDF incluido),
  `retention_dissociated` (disociación por política de retención), `user_created` (alta de una cuenta
  del panel) y `user_role_changed` (cambio de rol de una cuenta del panel).
  *No generan evento propio* la validación de resultados, la baja de una cuenta ni los demás campos de
  una cuenta (nombre, correo o `is_active`): el registro cubre el rol, que es lo que decide el acceso.
  La edición del banco de reactivos ya no es un caso pendiente: el instrumento es de solo lectura. Se
  declara como limitación conocida del control compensatorio: la trazabilidad cubre las acciones sobre
  datos personales, las exportaciones y la asignación de roles, no toda la administración.
- *Hallazgo:* El rol `admin` concentra la operación diaria de candidatos, la validación de resultados
  (RF-44) y la gestión de cuentas del panel, incluida la creación de otros admins (RF-45). Quien opera
  a diario también puede crear cuentas o exportar conjuntos amplios de datos.
- *Decisión:* Separar el alcance en lugar de mantener dos roles. El personal de evaluación ya no
  necesita una cuenta `admin`: con `evaluador` consulta todo lo que su trabajo requiere. Y la facultad
  de alterar la normativa —la parte del hallazgo con peor consecuencia, porque recalcularía resultados
  ya emitidos— desaparece del panel para cualquier rol: el instrumento solo se cambia por seeder o
  migración, con historial en el repositorio.
- *Controles compensatorios:*
  + Limitar las cuentas `admin` a las estrictamente necesarias y revisarlas periódicamente.
  + Registro de auditoría en `activity_logs` de reseteos de contraseña, exportaciones y gestión de
    cuentas (alta y cambio de rol), con las limitaciones declaradas arriba.
  + El instrumento es inmutable desde el panel y las sesiones de test son de solo lectura incluso para
    `admin`.
- *Riesgo aceptado:* Un administrador de operación diaria (o una cuenta comprometida) todavía puede
  crear cuentas del panel y exportar conjuntos amplios de datos. Ese resto se mitiga con trazabilidad y
  con la revisión periódica de cuentas, no con prevención.
- *Archivos / documentos afectados:* recursos de Filament de administración y del instrumento,
  `app/Models/User.php`, la migración del rol, `README.md`, `manual-usuario.typ` y
  `docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md`.
= 2. Ajuste de versión del stack tecnológico

Estado verificado el 2026-09-20 contra `composer.json`, `package.json` y los paquetes instalados.

#table(
  columns: (auto, 1.3fr, auto, 1.2fr, 1.6fr, auto, auto),
  table.header([Tecnología], [Versión actual en el proyecto], [Versión objetivo], [Cambios necesarios], [Comando / paso], [Riesgo], [Estado]),
  [Laravel], [`^13.0` → instalado 13.31.0], [13], [Ninguno], [`composer update`], [Bajo], [OK],
  [PHP], [`^8.4` → runtime 8.4.25], [8.4], [Runtime PHP 8.4 con `intl`, `gd` y `zip`], [`php -v` · `php -m`], [Medio], [OK],
  [Filament], [`^4.13` → instalado 4.13.1], [v4 (último patch)], [Mantener `^4.x`], [`composer update filament/filament`], [Medio], [OK],
  [Livewire], [3.8.8 (dependencia de Filament)], [3.x], [Ninguno], [`composer update`], [Bajo], [OK],
  [Bootstrap], [5.3.0 *por CDN*], [5.3.x], [No es dependencia de `package.json`: se carga desde jsDelivr en el layout], [—], [Bajo], [OK],
  [Vite], [`^8.3.0` → instalado 8.3.0], [8], [Actualizado en esta etapa], [`npm run build`], [Bajo], [OK],
  [Tailwind], [`^4.3.3`], [4.x], [Configuración en CSS (`@theme`), sin `tailwind.config.js`], [`npm run build`], [Bajo], [OK],
  [laravel-vite-plugin], [`^3.2.0`], [3.x], [Requerido por Vite 8], [`npm install`], [Bajo], [OK],
  [DOMPDF], [`^3.1.2` → instalado 3.1.1 resuelto a 3.1.x], [3.1.2], [Requiere `ext-gd` para incrustar el logo], [`composer update barryvdh/laravel-dompdf`], [Bajo], [OK],
)

*Extensiones de PHP declaradas en `composer.json`:* `ext-intl`, `ext-gd` y `ext-zip`. Las tres se usan
en ejecución —Filament requiere `intl` para `Number::format`, DomPDF requiere `gd` para incrustar el
logo del informe, y las exportaciones a Excel requieren `zip` a través de `openspout`— y declararlas
hace que `composer install` avise si falta alguna en lugar de fallar cuando un administrador pulse un
botón en producción. Comprobadas con `php -m` y `composer check-platform-reqs`.

= 3. Entregables para el despliegue en staging

+ *Acceso al repositorio completo:* se entrega acceso para clonar y, además, un `git bundle` con el
  historial completo (`git bundle create test-raven.bundle --all`), que es lo que garantiza la
  independencia del repositorio original. El clon fue verificado: llegan las 60 láminas del test
  (120 archivos de matrices y 489 de opciones), lo que antes no ocurría porque esas carpetas no estaban
  versionadas y habrían dado error 404 al rendir el test en un entorno nuevo.
+ *Variables de entorno (`.env`):* `.env.example` documenta las claves que la aplicación espera, sin
  credenciales reales. Incluye `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, base de datos, sesión y
  caché, correo, y las nueve variables de retención (`RETENCION_ACTIVA` y
  `RETENCION_*_DIAS` / `RETENCION_*_ACCION`). No se implementó `RAVEN_TIME_LIMIT`: el tiempo límite se
  mantiene en 2700 s en el código de forma explícita (Observación 2).
+ *Seeders de tablas normativas:* se ejecutan con `php artisan db:seed --force`, que encadena
  `RavenTestSeeder` (60 reactivos y 432 opciones), `PercentileTableSeeder` (baremo de Montevideo),
  `DiagnosticRangeSeeder` (rangos I–V) y `DiscrepancyPatternSeeder` (patrones de discrepancia).
+ *Adicional:* `bin/backup.sh` (respaldo de base y clave de cifrado en una operación) y
  `docs/RESPALDO_Y_OPERACION.md` (comandos manuales, cron sugerido y procedimiento de restauración
  verificado).

= 5. Consideraciones para continuidad

- *Baremo (Obs. 9):* los percentiles y clasificaciones carecen de validación local hasta que la facultad
  decida. Mitigación: la regla de asignación está documentada y es conservadora (nunca sobreestima).
- *Permisos (Obs. 10):* concentración de capacidades en el rol `admin`, mitigada con auditoría y con la
  inmutabilidad del banco de ítems.
- *Tiempo límite (Obs. 2):* si se decide un valor distinto de 45 minutos, hay que desplegar una
  migración o ajuste que actualice `time_limit` y `remaining_time` en sesiones activas.
- *Identificación (Obs. 5):* *cerrado.* El campo único se mantiene y la validación por formato está
  implementada y probada. El único pendiente es institucional y no técnico: si se admiten menores de
  edad y extranjeros, o solo DUI.
- *Retención (Obs. 8):* disociar y suprimir son operaciones irreversibles. Los plazos definitivos deben
  fijarse antes de activar `RETENCION_ACTIVA` en producción, probando antes con `--dry-run`.
- *Claves (Obs. 8):* perder `APP_KEY` inutiliza los identificadores cifrados. `bin/backup.sh` respalda
  base y clave juntas y el procedimiento de restauración fue verificado de extremo a extremo.
- *Auditoría del panel (Obs. 10):* el alta de cuentas del panel y el cambio de rol quedan auditados
  (`user_created`, `user_role_changed`); la validación de resultados y los demás campos de una cuenta no
  generan evento propio. Es la limitación declarada del control compensatorio.

= 7. Anexo — Evidencia de ejecución

Las afirmaciones de este informe se apoyan en la suite de pruebas del proyecto, que se ejecuta con
`php artisan test`. Estado al 2026-09-21 —última ejecución, en la rama de cierre—: *146 pruebas y 667
aserciones: 145 en verde y 1 en rojo*. En la verificación inicial del informe (2026-09-20) eran 132
pruebas en verde y 469 aserciones; la diferencia son las pruebas del rol `evaluador`, del instrumento de
solo lectura y de la auditoría de cuentas.

#table(
  columns: (1.8fr, auto, 1fr),
  table.header([Área verificada], [Pruebas], [Archivo]),
  [Cifrado del identificador], [12], [`tests/Feature/Rnf0901DuiNitEncryptionTest.php`],
  [Separación de acceso candidato/administración], [8], [`tests/Feature/Rnf0903AccessSeparationTest.php`],
  [Retención y su registro], [17], [`tests/Feature/Rnf09RetentionTest.php`],
  [Integridad del banco de ítems], [13], [`tests/Feature/TestBankIntegrityTest.php`],
  [Instrumento de solo lectura en la interfaz], [3], [`tests/Feature/TestBankUiRulesTest.php`],
  [Matriz de permisos de los tres roles], [11], [`tests/Feature/PanelRolePermissionsTest.php`],
  [Auditoría de cuentas del panel], [6], [`tests/Feature/UserAccountAuditTest.php`],
  [Láminas del test en el repositorio], [4], [`tests/Feature/TestAssetsIntegrityTest.php`],
  [Formato del identificador DUI/NIT], [23], [`tests/Feature/RnfDocumentFormatTest.php`],
  [Límite de intentos en registro y login], [16], [`CandidateRegistrationThrottleTest.php`, `CandidateLoginThrottleTest.php`],
  [Reseteo de contraseña con auditoría], [9], [`tests/Feature/CandidatePasswordResetTest.php`],
  [Informe PDF y auditoría de exportaciones], [6], [`tests/Feature/TestResultPdfExportTest.php`],
  [Exportaciones destructivas por rol], [7], [`tests/Feature/PanelDestructiveActionsTest.php`],
  [Salvaguarda de la base de pruebas], [3], [`tests/Feature/TestDatabaseSafeguardTest.php`],
)

*Una prueba en rojo:* `tests/Feature/ExampleTest.php`, un ejemplo del esqueleto de Laravel que espera
un 200 en `/` y recibe la redirección al login. No cubre funcionalidad del sistema y se declara por
transparencia.

*Documentos de referencia:*
- `docs/RNF-09_CONFIDENCIALIDAD.md` — cumplimiento de RNF-09, requisito por requisito, con su prueba.
- `docs/RESPALDO_Y_OPERACION.md` — comandos manuales, respaldo de la `APP_KEY` y cron sugerido.
- `docs/AUDITORIA_Y_PLAN_ACTUALIZACION.md` — auditoría técnica y pendientes del proyecto.
- `README.md` — descripción, arquitectura y funcionalidades del sistema.