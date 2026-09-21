

#set page(
  paper: "a4",
  margin: (left: 2.5cm, right: 2.5cm, top: 2cm, bottom: 2cm),
  numbering: "1.1",
  header: align(center)[
    #text(size: 8pt, weight: "light", "Manual de Usuario — Test de Matrices Progresivas de Raven")
  ],
  footer: {
    align(center)[
      #context (text(size: 8pt, weight: "light", "Página ") + counter(page).display("1"))
    ]
  }
)

#set text(
  font: "DejaVu Sans",
  size: 11pt,
  lang: "es"
)

#set heading(
  numbering: "1.1",
  outlined: true
)

#show heading.where(level: 1): it => [
  #set align(center)
  #block(width: 100%)[#it]
  #v(1em)
]

#show heading.where(level: 2): it => [
  #block(width: 100%, fill: rgb("#E8F0FF"), inset: 6pt, radius: 4pt)[#it]
  #v(0.5em)
]

#show heading.where(level: 3): it => [
  #text(weight: "bold", size: 12pt, fill: rgb("#0047AB"))[#it]
  #v(0.3em)
]

#let info-box(title, body) = {
  block(
    fill: rgb("#F8FBFF"),
    inset: 10pt,
    radius: 4pt,
    width: 100%
  )[
    #text(weight: "bold", size: 11pt, fill: rgb("#0047AB"))[#title]
    #v(0.3em)
    #body
  ]
}

#let warn-box(title, body) = {
  block(
    fill: rgb("#FFF9E6"),
    inset: 10pt,
    radius: 4pt,
    width: 100%
  )[
    #text(weight: "bold", size: 11pt, fill: rgb("#B8860B"))[#title]
    #v(0.3em)
    #body
  ]
}

#let tip-box(title, body) = {
  block(
    fill: rgb("#E6F0FF"),
    inset: 10pt,
    radius: 4pt,
    width: 100%
  )[
    #text(weight: "bold", size: 11pt, fill: rgb("#0047AB"))[#title]
    #v(0.3em)
    #body
  ]
}

#let key-value(key, value) = {
  table(
    columns: (auto, 1fr),
    stroke: none,
    inset: 4pt,
    [#text(weight: "bold", fill: rgb("#0047AB"))[#key]], [#value],
  )
}

#let make-table(headers, rows, col-widths: none) = {
  let cols = if col-widths == none { (1fr,) * headers.len() } else { col-widths }
  let n-data = calc.quo(rows.flatten().len(), headers.len())
  table(
    columns: cols,
    inset: 7pt,
    align: horizon,
    stroke: none,
    table.hline(y: 0, stroke: 1pt + rgb("#333333")),
    table.header(..headers.map(h => text(weight: "semibold", size: 9.5pt)[#h])),
    table.hline(y: 1, stroke: 0.5pt + rgb("#333333")),
    ..rows.flatten().map(c => text(size: 9.5pt)[#c]),
    table.hline(y: 1 + n-data, stroke: 1pt + rgb("#333333")),
  )
}

#let toc = {
  outline(title: [Tabla de Contenidos], indent: 1.5em, depth: 2)
}

#let portada = {
  align(center)[
    #v(2.0cm)
    #image("assets/ues1.png", width: 4.5cm)
    #v(1.8cm)
    #text(size: 13pt, weight: "semibold", tracking: 3pt, fill: rgb("#222222"))[UNIVERSIDAD DE EL SALVADOR]
    #v(0.5cm)
    #text(size: 10pt, fill: rgb("#777777"))[Sistema de Evaluación Psicométrica en Línea]
    #v(2.4cm)
    #line(length: 30%, stroke: 0.75pt + rgb("#999999"))
    #v(0.5cm)
    #text(size: 28pt, weight: "bold", fill: rgb("#1a1a1a"))[Manual de Usuario]
    #v(0.5cm)
    #text(size: 16pt, fill: rgb("#333333"))[
      Test de Matrices Progresivas de Raven
    ]
    #v(0.35cm)
    #text(size: 12pt, style: "italic", fill: rgb("#666666"))[Escala General para Adultos (SPM)]
    #v(2.6cm)
    #line(length: 30%, stroke: 0.75pt + rgb("#999999"))
    #v(0.9cm)
    #text(size: 10pt, fill: rgb("#555555"))[Versión 1.0]
    #v(0.25cm)
    #text(size: 10pt, fill: rgb("#888888"))[Agosto 2026]
  ]
}

#portada
#pagebreak()

#toc
#pagebreak()

= Introducción

== Propósito del Manual
Este manual proporciona una guía completa para el uso del *Sistema de Evaluación Psicométrica basado en el Test de Matrices Progresivas de Raven (SPM)*. Está dirigido a dos perfiles de usuario:

- *Candidatos*: Personas que realizan el test para evaluar su capacidad de razonamiento abstracto.
- *Administradores*: Personal encargado de gestionar candidatos, consultar resultados y generar reportes.

== Acerca del Test de Raven (SPM)
El *Test de Matrices Progresivas Estándar (Standard Progressive Matrices — SPM)* es una prueba no verbal diseñada por John C. Raven para medir el *factor "g" de inteligencia general*, específicamente la capacidad de *razonamiento análogo, observación y pensamiento lógico abstracto*, minimizando la influencia de factores culturales y educativos.

#key-value("Formato", "60 ítems distribuidos en 5 series (A, B, C, D, E) de 12 preguntas cada una")
#key-value("Tiempo límite", "45 minutos (2700 segundos)")
#key-value("Opciones por ítem", "Series A y B: 6 opciones / Series C, D, E: 8 opciones")
#key-value("Población objetivo", "Adolescentes y adultos (desde 12 años)")
#key-value("Baremo utilizado", "Normas de Montevideo (adaptación local validada)")
#key-value("Administración", "Individual o colectiva, autoadministrado en plataforma web")

== Características Principales del Sistema
- *Acceso web responsivo*: Funciona en navegadores modernos (Chrome, Firefox, Edge, Safari).
- *Timer robusto*: Reloj en segundo plano (Web Worker) inmune a cambios de pestaña o suspensión del sistema.
- *Una sola oportunidad*: El test solo puede realizarse *una vez* por candidato.
- *Sin retroceso*: No es posible regresar a preguntas anteriores.
- *Cálculo automático*: Puntajes, percentil, rango diagnóstico y validación de consistencia (±2).
- *Reportes en PDF*: Informe profesional descargable con interpretación textual.
- *Panel administrativo*: Filament v4 con roles, filtros, exportación Excel/CSV/PDF.

#pagebreak()

= Guía para Candidatos

== Requisitos Previos
- Navegador actualizado (Chrome 90+, Firefox 88+, Edge 90+, Safari 14+).
- Conexión a internet estable.
- JavaScript y cookies habilitados.
- 45-60 minutos de tiempo ininterrumpido.
- Ambiente tranquilo, sin distracciones.

== 1. Registro de Cuenta
#tip-box("Antes de empezar", [
  Ten a mano tu *DUI/NIT* y un *correo electrónico válido*. El sistema valida unicidad de ambos campos.
])

1. Accede a la URL del sistema.
2. Haz clic en *"Registrarse"* o ve directamente a `/register`.
3. Completa el formulario:
   - *Nombre completo* (obligatorio).
   - *Correo electrónico* (obligatorio, único).
   - *DUI / NIT* (obligatorio, único, formato nacional).
   - *Contraseña* (mínimo 8 caracteres, confirmación).
   - *Edad* (número entero, obligatorio — determina baremo normativo).
   - *Ocupación* (texto libre, obligatorio).
   - *Nivel educativo* (selección: bachillerato, técnico, universitario, posgrado).
4. Pulsa *"Crear cuenta"*.
5. Serás redirigido automáticamente a la página de *Instrucciones*.

== 2. Inicio de Sesión
1. En la página principal (`/login`), ingresa tu *correo* *o* *DUI/NIT* y tu *contraseña*.
2. Opcional: marca *"Recordarme"* para mantener la sesión.
3. Pulsa *"Iniciar sesión"*.
4. Si las credenciales son correctas, accederás a la *pantalla de Bienvenida/Instrucciones*.

== 3. Pantalla de Instrucciones (Bienvenida)
Esta pantalla muestra:
- Tus datos personales (nombre, edad, ocupación, educación).
- *Instrucciones detalladas* del test.
- *Advertencias críticas* (tiempo límite, no retroceso, una sola oportunidad).
- *Recomendaciones* para un desempeño óptimo.

#warn-box("Reglas inquebrantables", [
  - El test dura *45 minutos exactos*. El reloj *no se detiene* si cambias de pestaña o pierdes conexión.
  - *No puedes volver atrás* una vez respondida una pregunta.
  - *Solo tienes UN intento*. Si completas el test, no podrás repetirlo.
  - No cierres el navegador ni actualices la página durante el test.
])

Cuando estés listo, pulsa el botón *"INICIAR TEST AHORA"*.

== 4. Realización del Test

=== Interfaz de Pregunta
La pantalla se divide en tres áreas:
1. *Cabecera*: Número de pregunta actual (1–60), serie (A–E), barra de progreso y *temporizador* (MM:SS).
2. *Matriz central*: Figura geométrica con una pieza faltante (imagen).
3. *Cuadrícula de opciones*: 6 u 8 imágenes según la serie. Al hacer clic, la opción se *resalta en azul* con una marca de verificación.
4. *Botón "Siguiente"*: Se habilita solo tras seleccionar una opción.

=== Flujo por pregunta
1. Observa la matriz e identifica el patrón lógico.
2. Haz clic en la opción que completa correctamente la figura.
3. Verifica que la opción seleccionada sea la deseada (borde azul + check ✓).
4. Pulsa *"Siguiente"*.
5. El sistema guarda tu respuesta, calcula el tiempo empleado y avanza a la siguiente pregunta.

=== Temporizador (Timer)
- Se muestra en formato *MM:SS* en la esquina superior derecha.
- *Color normal*: negro/azul.
- *Advertencia (≤ 5 min)*: fondo rojo, texto blanco.
- *Crítico (≤ 1 min)*: parpadeo visual.
- *Agotado (0:00)*: el test se finaliza *automáticamente* y guarda lo respondido.

#info-box("Web Worker", [
  El timer corre en un *hilo separado (Web Worker)*. Esto garantiza que *no se retrase* aunque el navegador esté ocupado o la pestaña en segundo plano. El tiempo se calcula con *fecha absoluta del servidor* (`expires_at` ISO 8601), eliminando deriva horaria.
])

=== Tiempo por pregunta
El sistema registra automáticamente los *segundos transcurridos* entre preguntas (`time_spent`). Este dato se usa para:
- Tiempo total del test.
- Tiempo medio por pregunta.
- Análisis de velocidad de procesamiento.

== 5. Finalización del Test
El test termina automáticamente cuando:
- *Respondes las 60 preguntas* → Estado `completed`.
- *Se agota el tiempo (45 min)* → Estado `timeout`.

#make-table(
  ("Sección", "Contenido"),
  (
    "Puntaje Total", "Aciertos totales / 60 (display grande).",
    "Percentil", "Posición relativa respecto al grupo normativo de tu edad (1–99).",
    "Rango Diagnóstico", "Romano (I a V) + etiqueta (ej. \"Superior al Término Medio\").",
    "Tiempo Total", "Formato MM:SS.",
    "Estado de Validez", "*Válido* (verde) / *Inválido* (rojo) con notas si aplica.",
    "Puntajes por Serie", "A, B, C, D, E — cada una /12."
  )
)

Si el resultado es *Inválido*, verás una *advertencia amarilla* explicando la discrepancia detectada (ej. "Serie C: esperado 8, obtuvo 4 (diferencia: -4)").

== 7. Cierre de Sesión
Pulsa *"Cerrar Sesión"* para finalizar. Tus datos y resultados quedan almacenados de forma segura.

#pagebreak()

= Guía para Administradores

== Acceso al Panel Administrativo
1. Navega a `/admin`.
2. Inicia sesión con tu *usuario administrativo* (proveído por TI).
3. Solo usuarios con rol `admin`, `reporter` o `evaluador` pueden acceder.

== Roles y Permisos

#make-table(
  ("Rol", "Permisos"),
  (
    "admin", "Acceso total: ver/editar/eliminar candidatos, ver/exportar resultados, gestionar usuarios del panel, dashboard.",
    "reporter", "Solo lectura: listar candidatos, listar resultados, exportar Excel/CSV/PDF. *No* puede editar, eliminar ni gestionar usuarios.",
    "evaluador", "Solo lectura de las evaluaciones (candidatos, sesiones y resultados) y del instrumento (reactivos, series, baremos y rangos diagnósticos). Puede descargar el informe PDF individual; *no* tiene exportación masiva a Excel/CSV ni acceso a usuarios."
  )
)

*El instrumento es de solo lectura.* Las series, los reactivos, el baremo y los rangos diagnósticos se
consultan desde el panel, pero ningún rol —tampoco `admin`— puede crearlos, modificarlos ni borrarlos
ahí: determinan el puntaje y el diagnóstico de los tests ya rendidos, así que cualquier cambio se hace
por seeder o migración, con control de versiones y rastro.

*Trazabilidad de los accesos.* El alta de una cuenta del panel y todo cambio de rol quedan registrados
en la auditoría (`activity_logs`), con la fecha, quién lo hizo y el rol anterior y el nuevo.

== Dashboard Principal
Al entrar verás widgets con *KPIs clave*:
- Total de candidatos registrados.
- Tests completados / pendientes.
- Distribución por rangos diagnósticos.
- Promedio de percentil global.
- Gráficas de tendencias (últimos 30 días).

== Gestión de Candidatos (`/admin/candidates`)

=== Listado con Pestañas
- *Todos*: Todos los registros.
- *Pendientes* (amarillo): `test_completed = false`.
- *Completados* (verde): `test_completed = true`.
- *Inactivos*: `is_active = false`.

=== Filtros Disponibles
- Rango de edad (slider o inputs numéricos).
- Nivel educativo (multiselect).
- Estado del test (completado/pendiente).
- Búsqueda por nombre, email, DUI/NIT.
- Ordenamiento por cualquier columna (fecha, nombre, edad, estado).

=== Acciones por Candidato
- *Ver* (ojo): Detalle completo + resultado si existe.
- *Editar* (lápiz): Solo `admin` — modificar datos personales.
- *Eliminar* (papelera): Soft delete — solo `admin`.

=== Infolist (Vista Detalle)
Al hacer clic en *Ver*, se muestra:
- *Datos Personales*: Nombre, email, edad, ocupación, educación.
- *Resultado Final* (si completado): Puntaje total, percentil, diagnóstico (badges de color).

== Gestión de Resultados (`/admin/test-results`)

=== Tabla de Resultados
Columnas principales:
- Candidato (nombre + enlace a ficha).
- Edad.
- Puntaje Total (/60).
- Percentil.
- Rango Diagnóstico (badge coloreado).
- Validez (badge verde/rojo).
- Tiempo Total.
- Fecha de evaluación.

=== Filtros Avanzados
- Rango diagnóstico (I, II+, II, III+, III, III-, IV+, IV, V).
- Validez (Válido / Inválido).
- Rango de puntaje total (slider 0–60).
- Rango de percentil (slider 1–99).
- Rango de fechas (date picker).
- Edad del candidato.

=== Infolist de Resultado
Organizado en secciones:
1. *Datos del Evaluado* (nombre, edad, DUI, email, ocupación, educación, fecha, tiempo, estado).
2. *Puntajes por Serie* (tabla A–E + Total).
3. *Diagnóstico* (percentil grande, badge rango, etiqueta).
4. *Interpretación* (texto completo del rango diagnóstico).
5. *Observaciones de Validez* (solo si inválido).

== Exportación de Datos

=== Excel (Filament Excel)
1. En cualquier listado (Candidatos o Resultados), aplica los *filtros deseados*.
2. Pulsa el botón *"Exportar a Excel"* en la cabecera de la tabla.
3. Se descarga un `.xlsx` con *exactamente las filas y columnas visibles*.

=== CSV (Filament Excel)
1. En cualquier listado (Candidatos o Resultados), aplica los *filtros deseados*.
2. Pulsa el botón *"Exportar a CSV"* en la cabecera de la tabla.
3. Se descarga un `.xlsx` con *exactamente las filas y columnas visibles*.

=== PDF Individual (Reporte Profesional)
1. En la vista detalle de un *Resultado* (infolist), pulsa *"Descargar PDF"*.
2. Se genera un reporte con:
   - Encabezado institucional (logo UES, título, "Escala General para Adultos").
   - Datos del evaluado en tabla formateada.
   - Tabla de puntajes por serie (A–E + Total).
   - Caja de diagnóstico: percentil grande (42pt), badge rango rojo, clasificación.
   - Interpretación textual completa (desde `DiagnosticRange.interpretation`).
   - Notas de validez (si aplica, caja amarilla).
   - Pie de página: fecha generación, normas Montevideo, aviso confidencialidad.

#info-box("Formato PDF", [
  El reporte usa *DomPDF* con plantilla Blade optimada para impresión (márgenes 25px, fuentes DejaVu Sans, colores corporativos UES: `#0047AB` azul, `#FFCC00` oro, `#E60000` rojo).
])

#pagebreak()

= Referencia Técnica: Cálculo e Interpretación

== Esquema de Puntuación
- Cada respuesta *correcta = 1 punto*.
- Cada respuesta *incorrecta = 0 puntos*.
- No hay penalización por error.
- *Puntaje máximo = 60* (12 por serie × 5 series).

== Series y Dificultad Progresiva

#make-table(
  ("Serie", "Ítems", "Opciones", "Dificultad", "Habilidad principal"),
  (
    "A", "12", "6", "Muy baja", "Percepción de patrones simples, continuidad.",
    "B", "12", "6", "Baja", "Analogía simple, completación.",
    "C", "12", "8", "Media", "Permutación, alternancia, progresión.",
    "D", "12", "8", "Alta", "Combinación de reglas, superposición.",
    "E", "12", "8", "Muy alta", "Múltiples reglas simultáneas, razonamiento complejo."
  )
)

== Cálculo del Percentil (Baremo de Montevideo)
1. Se obtiene el *raw_score* (puntaje bruto 0–60).
2. Se busca en la tabla `percentile_tables` el registro que coincida con:
   - *Grupo de edad* del candidato (8 bandas: 12, 13–14, 15–16, 17, 18, 19, 20–21, 22–65).
   - *Puntaje exacto* o, si no existe, el *inferior más cercano* (interpolación hacia abajo).
3. Se asigna el *percentil* correspondiente (1–99).

#warn-box("Edad crítica", [
  La *edad declarada al registrarse* determina el grupo normativo. Un error de incluso 1 año puede cambiar el percentil asignado. Verifica la edad antes de iniciar el test.
])

== Rangos Diagnósticos (9 Niveles)
La tabla `diagnostic_ranges` mapea percentil → rango:

#make-table(
  ("Rango", "Sub-rango", "Percentil", "Etiqueta", "Interpretación resumida"),
  (
    "*I*", "I", "95–100", "Intelectualmente Superior", "Capacidad > 95% población. Razonamiento analógico sobresaliente.",
    "*II*", "II+", "90–94", "Superior al Término Medio", "Claramente superior al promedio. Percentil > 90.",
    "", "II", "75–89", "Superior al Término Medio", "Definitivamente superior al promedio. Buen razonamiento no verbal.",
    "*III*", "III+", "51–74", "Término Medio", "Promedio, tendencia superior. Razonamiento adecuado.",
    "", "III", "50", "Término Medio", "Exactamente en el promedio esperado.",
    "", "III-", "26–49", "Término Medio", "Promedio, tendencia inferior.",
    "*IV*", "IV+", "11–25", "Inferior al Término Medio", "Por debajo del promedio. Posibles dificultades en razonamiento abstracto.",
    "", "IV", "6–10", "Inferior al Término Medio", "Definitivamente por debajo del promedio. Evaluación complementaria recomendada.",
    "*V*", "V", "0–5", "Deficiente", "Significativamente por debajo. Evaluación detallada obligatoria."
  )
)

== Validación de Consistencia (Discrepancia ±2)
El sistema verifica que el *perfil de scores por serie* sea coherente con el *puntaje total*, según la tabla oficial Raven (`discrepancy_patterns`):

- Para cada puntaje total (0–60) existen *puntajes esperados* por serie (A–E).
- *Criterio*: Ninguna serie puede desviarse *más de ±2 puntos* de su valor esperado.
- Si *alguna serie* supera ±2 → *Resultado = Inválido*.
- Las notas de validez detallan qué series fallan y en cuánto.

#info-box("Ejemplo", [
  Puntaje total = 40. Esperado: A=10, B=9, C=8, D=9, E=4.
  Candidato obtiene: A=10, B=9, C=5, D=9, E=4.
  → Serie C: diferencia = -3 (> 2) → *Inválido*.
  Nota: "Serie C: esperado 8, obtuvo 5 (diferencia: -3)".
])

=== Causas comunes de inválido
- Fatiga o desatención en series medias/altas (C, D, E).
- Adivinación aleatoria en ítems difíciles.
- Problemas técnicos (imágenes no cargadas, errores de red).
- Estrategia de respuesta inconsistente.

== Tiempos Registrados
- `total_time_seconds`: Segundos desde `started_at` hasta `completed_at` (o timeout).
- `average_time_per_question`: `total_time_seconds / preguntas_respondidas`.
- Se muestran en resultados y reportes PDF.

#pagebreak()

= Solución de Problemas (FAQ)

== Candidatos

=== "No puedo iniciar sesión"
- Verifica que uses *email o DUI/NIT* (no ambos).
- Revisa mayúsculas/minúsculas en contraseña.
- Si olvidaste la contraseña: contacta al administrador (no hay recuperación automática implementada).

=== "El test no carga / imágenes rotas"
- Recarga la página (F5 / Ctrl+R).
- Verifica conexión a internet.
- Si persiste: *contacta al administrador* — las imágenes del test deben estar en `storage/app/public/test-images/`.

=== "El timer se ve raro / no avanza"
- El timer usa *Web Worker*. Algunos navegadores antiguos o modo "ahorro de energía" pueden interferir.
- Prueba en *Chrome/Edge/Firefox actualizados*.
- No abras herramientas de desarrollador (F12) durante el test — puede pausar el worker.

=== "Me salió 'Tiempo agotado' pero quedaba tiempo"
- El tiempo es *absoluto del servidor* (`expires_at`). Si tu reloj local tiene desfase, la visualización puede diferir.
- El servidor es la autoridad final. Al agotarse, el test se finaliza *inmediatamente*.

=== "Quiero repetir el test"
*No es posible*. El sistema bloquea nuevos intentos si `test_completed = true`. Contacta al administrador si hubo incidencia técnica grave.

== Administradores

=== "No veo candidatos en el panel"
- Verifica tu rol: `admin`, `reporter` o `evaluador`.
- Revisa filtros activos (pestaña "Pendientes" oculta completados).
- Ejecuta `php artisan filament:upgrade` si actualizaste Filament.

=== "El PDF no se genera / sale en blanco"
- Requiere `barryvdh/laravel-dompdf` instalado.
- Verifica que la vista `reports.raven-result` exista y compile.
- Comprueba logs: `storage/logs/laravel.log`.

=== "La exportación Excel no respeta filtros"
- Filament Excel exporta *lo visible en la tabla* (paginación, filtros, orden).
- Asegúrate de *aplicar filtros antes* de exportar.
- Si usas "Seleccionar todo", exporta todas las páginas filtradas.

=== "Resultados inválidos masivos"
- Revisa si las *imágenes del test* se sirven correctamente (`php artisan storage:link`).
- Verifica que el seeder `RavenTestSeeder` pobló `test_questions` y `answer_options` con `correct_answer` correctos.
- Comprueba `PercentileTable` y `DiscrepancyPattern` tienen datos (`php artisan db:seed`).

#pagebreak()

= Apéndices

== A. Glosario de Términos

#make-table(
  ("Término", "Definición"),
  (
    "SPM", "Standard Progressive Matrices — versión estándar del test de Raven.",
    "Raw Score*", "Puntaje bruto: total de aciertos (0–60).",
    "Percentil", "Porcentaje de población normativa que obtiene un puntaje *igual o inferior*. P50 = promedio.",
    "Rango Diagnóstico", "Clasificación categórica (I–V) basada en percentil.",
    "Baremo", "Tabla de referencia que convierte raw score → percentil según edad.",
    "Discrepancia", "Diferencia entre score observado y esperado por serie. > ±2 = inválido.",
    "Web Worker", "Hilo JS separado para timer preciso sin throttling de pestaña.",
    "Soft Delete", "Eliminación lógica (registro oculto, no borrado de BD)."
  )
)

== B. Estructura de Base de Datos (Resumen)
```
candidates           ← Usuarios candidatos (auth)
test_sessions        ← Sesiones de test (estado, tiempo, progreso)
test_questions       ← 60 ítems (serie, orden, imagen, respuesta correcta)
answer_options       ← Opciones visuales por pregunta (imagen)
test_answers         ← Respuestas del candidato (ítem, opción, tiempo, correcta)
test_results         ← Cálculo final (scores, percentil, rango, validez)
percentile_tables    ← Baremo Montevideo (edad × raw_score → percentil)
diagnostic_ranges    ← 9 rangos diagnósticos (percentil → etiqueta + interpretación)
discrepancy_patterns ← Scores esperados por serie según total (validación ±2)
activity_logs        ← Auditoría de acciones relevantes
users                ← Usuarios administradores (panel Filament)
```

== C. Comandos Útiles para Administración
```bash
# Instalación inicial
composer install
npm install && npm run build
php artisan migrate --seed
php artisan storage:link
php artisan filament:install --panels
php artisan shield:install --fresh
php artisan shield:generate --all

# Usuario admin inicial
php artisan tinker
>>> App\Models\User::create(['name'=>'Admin','email'=>'admin@ues.edu.sv','password'=>bcrypt('password'),'role'=>'admin'])

# Seeders específicos
php artisan db:seed --class=RavenTestSeeder
php artisan db:seed --class=PercentileTableSeeder
php artisan db:seed --class=DiagnosticRangeSeeder
php artisan db:seed --class=DiscrepancyPatternSeeder

# Limpieza y recálculo (si se corrigen datos)
php artisan tinker
>>> App\Models\TestResult::truncate()
>>> App\Models\TestSession::where('status','completed')->get()->each(fn($s) => app(App\Services\ResultCalculatorService::class)->calculateResult($s))
```

== D. Variables de Entorno Relevantes (`.env`)
```env

```

== E. Confidencialidad de los Datos (RNF-09)

El sistema trata los datos personales según los requisitos *RNF-09* (Ley de Protección de Datos
Personales de El Salvador, D.L. 144/2024). Tres piezas lo sostienen: el cifrado en reposo, la
separación de accesos y la retención con supresión.

*Cifrado en reposo.* El *DUI/NIT* se guarda cifrado con *AES-256-CBC*: en la base de datos no queda en
claro. Para buscar a un candidato y para comprobar que nadie se registre dos veces se usa un índice
aparte (`dui_nit_hash`), un *HMAC-SHA256* calculado con la clave de la aplicación, que permite
localizarlo *sin descifrar* su documento. El identificador se muestra completo en las vistas del
personal autorizado (listado de candidatos, exportación CSV e informe PDF). Si la clave `APP_KEY` se
pierde, los identificadores cifrados no se pueden recuperar, así que debe respaldarse junto con la base
de datos.

*Separación de accesos.* El candidato entra con su propia cuenta (guard `candidate`) y solo alcanza su
sesión de test: no puede ver ni responder por otro candidato, y no tiene acceso a resultados,
puntajes, percentiles ni clasificación diagnóstica. Esos datos viven únicamente en el panel
administrativo. La pantalla final del candidato confirma que el test se completó, no muestra el
resultado.

*Retención y supresión.* Los datos vencidos se eliminan o se anonimizan automáticamente. Los plazos y
las acciones se configuran por variable de entorno (`.env`), sin tocar código:

#make-table(
  ("Categoría", "Qué alcanza", "Variable de plazo", "Acción"),
  (
    "Datos identificativos", "Nombre, correo, DUI/NIT, edad, ocupación y nivel educativo", "RETENCION_PERSONAL_DIAS", "Disociar",
    "Expediente psicométrico", "Resultados, puntajes, percentiles, diagnósticos y respuestas", "RETENCION_PSICOMETRICO_DIAS", "Disociar",
    "Logs de auditoría", "Registro de las acciones del sistema", "RETENCION_ACTIVIDAD_DIAS", "Suprimir",
    "Trazas técnicas", "Direcciones IP, navegador y dispositivo", "RETENCION_TECNICO_DIAS", "Suprimir"
  ),
  col-widths: (auto, 2.2fr, auto, auto)
)

*Disociar* borra los datos que identifican a la persona y conserva la información estadística;
*suprimir* elimina el dato por completo. El interruptor general es `RETENCION_ACTIVA`. La aplicación es
`php artisan retention:apply` —con `--dry-run` para revisar el alcance sin modificar nada—, programada
a diario, y cada ejecución queda registrada como evidencia.

#pagebreak()
---
*Desarrollado por:*
- Reyna Guadalupe Miranda Rivas, RM21082
- Milton Obed Alas Hernandez, AH09062
