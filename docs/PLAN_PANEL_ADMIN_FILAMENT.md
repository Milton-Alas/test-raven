# Plan Simplificado - Panel Admin de Resultados con Filament v4

> **Estado (cierre del proyecto):** este documento es el plan de diseño con el que se construyó el
> panel, no su descripción final. Entre otras diferencias, la autorización se resolvió con el rol en
> `users.role` (`admin`, `reporter`, `evaluador`) y los métodos `canX()` de cada recurso de Filament:
> **no se instalaron `spatie/laravel-permission` ni Filament Shield**, así que los pasos de instalación
> de esos paquetes no aplican. La descripción del panel tal como quedó está en el `README.md`.

## 1. Objetivo

Implementar un panel administrativo simple y robusto para que un usuario con rol `admin` pueda:

- Consultar candidatos y sus resultados.
- Filtrar y analizar resultados de forma visual.
- Exportar reportes en Excel y PDF.
- Operar el sistema con permisos claros y navegación ordenada.

## 2. Alcance (versión reducida)

Incluye:

- Configuración de Filament v4 y panel administrativo.
- Recurso completo de candidatos.
- Recurso completo de resultados.
- Exportaciones (Excel y PDF).
- Gestión básica de usuarios administrativos y roles.
- Dashboard con métricas clave.
- Políticas de acceso, navegación final y pruebas.

No incluye en esta fase:

- Flujo de revisión manual multiestado (`approved/rejected/...`).
- Recursos operativos avanzados (`TestSession`, banco completo de preguntas).
- Auditoría avanzada de exportes o colas complejas.

## 3. Stack Técnico

- `laravel/framework`
- `filament/filament:^4.0`
- `bezhansalleh/filament-shield` (roles/permisos en Filament)
- `spatie/laravel-permission` (base de roles/permisos)
- `pxlrbt/filament-excel` (exportación desde tablas)
- `barryvdh/laravel-dompdf` (exportación PDF)

## 4. Roadmap de Implementación

### FASE 4.1: Configuración de Filament v4

- Configuración de `AdminPanelProvider`.
- Configuración de Shield para roles/permisos.
- Personalización del panel (colores, nombre, favicon).
- Definición de grupos de navegación.

Entregables:

- `/admin` operativo con autenticación.
- Panel con branding básico.
- Roles iniciales: `admin`, `reporte`.

### FASE 4.2: Recurso de Candidatos (COMPLETO)

Implementar `CandidateResource` full-featured:

- Formulario completo por secciones.
- Tabla con columnas personalizadas.
- Filtros: edad, educación, estado.
- Búsqueda y ordenamiento.
- Soft deletes.
- Infolist detallado.
- Badges y colores dinámicos.
- Acción para ver resultados del candidato.

Implementar `ListCandidates` con tabs:

- Todos.
- Pendientes (`warning`).
- Completados (`success`).
- Inactivos.

Entregables:

- Recurso funcional y usable por `admin`.
- Navegación rápida desde candidato hacia resultados.

### FASE 4.3: Recurso de Resultados

Implementar `TestResultResource` full-featured:

- Tabla con resultados detallados.
- Filtros por rango, validez, puntaje, fecha.
- Infolist con información completa.
- Secciones organizadas para lectura rápida.
- Badges con colores inteligentes.
- Botón de descarga PDF por resultado.

Entregables:

- Consulta centralizada de resultados.
- Flujo de exportación individual en PDF.

### FASE 4.4: Paquetes de Exportación

- Instalación de Filament Excel.
- Configuración para exportación a Excel desde tablas.

Entregables:

- Exportación XLSX/CSV desde `CandidateResource` y/o `TestResultResource`.

### FASE 4.5: Exportación a PDF

- Controller para generar PDFs.
- Vista Blade de reporte PDF.
- Integración con DomPDF.

Entregables:

- PDF descargable por resultado.
- Plantilla estándar de reporte.

### FASE 4.6: Recurso de Usuarios Admin

- Gestión de usuarios administrativos.
- Asignación de roles (`admin`, `reporte`).
- Filtros por estado/rol.
- Restricciones de acceso por permisos.

Entregables:

- Alta/edición/baja lógica de usuarios admin.
- Control de acceso basado en roles.

### FASE 4.7: Dashboard Personalizado

- Widgets de estadísticas.
- Gráficas de resultados.
- Resumen general para monitoreo rápido.

Entregables:

- Dashboard inicial con KPIs útiles para operación diaria.

### FASE 4.8: Configuración Final

- Policies de acceso.
- Navegación final.
- Testing de flujos principales.

Entregables:

- Seguridad básica validada.
- Menú final limpio y consistente.
- Suite mínima de pruebas en verde.

## 5. Backlog Priorizado (Tickets)

Convención:

- Prioridad: `P0` crítica, `P1` alta, `P2` media.
- Estimación en días hábiles efectivos.

### Sprint A - Base y seguridad

1. `ADM-401` Configurar Filament v4 + AdminPanelProvider (`P0`, 0.5d)
2. `ADM-402` Integrar Shield + permisos base (`P0`, 1d)
3. `ADM-403` Branding de panel y navegación (`P1`, 0.5d)

### Sprint B - Candidatos

1. `ADM-404` `CandidateResource` completo (`P0`, 2d)
2. `ADM-405` Tabs en `ListCandidates` (`P1`, 0.5d)
3. `ADM-406` Acción "Ver resultados" por candidato (`P0`, 0.5d)

### Sprint C - Resultados y exportes

1. `ADM-407` `TestResultResource` completo (`P0`, 1.5d)
2. `ADM-408` Integrar Filament Excel (`P1`, 0.5d)
3. `ADM-409` Exportación PDF con DomPDF (`P1`, 1d)

### Sprint D - Usuarios, dashboard y cierre

1. `ADM-410` Recurso de usuarios admin + roles (`P1`, 1d)
2. `ADM-411` Dashboard con widgets y gráficas (`P2`, 1d)
3. `ADM-412` Policies + testing + ajustes finales (`P0`, 1.5d)

## 6. Criterios de Cierre (Definition of Done)

- Panel `/admin` operativo con autenticación.
- Solo usuarios autorizados acceden a candidatos/resultados.
- `CandidateResource` y `TestResultResource` completos y funcionales.
- Exportación a Excel y PDF disponible.
- Dashboard con métricas básicas activo.
- Pruebas mínimas de acceso y flujos clave en verde.

## 7. Riesgos y Mitigaciones

1. Permisos mal configurados
- Mitigación: matriz rol-permiso + tests de autorización.

2. Consultas lentas en listados
- Mitigación: índices, eager loading y paginación correcta.

3. Inconsistencia en datos de resultados
- Mitigación: validaciones en modelos/queries y checks en infolists.

4. Formato PDF inconsistente
- Mitigación: plantilla única, pruebas de render y control de estilos.

## 8. Planes Requeridos para Iniciar

### 8.1 Plan de Arranque Técnico (Semana 1)

Objetivo: dejar base operativa para construir recursos.

Checklist:

1. Instalar dependencias base:
- `filament/filament:^4.0`
- `spatie/laravel-permission`
- `bezhansalleh/filament-shield`

2. Ejecutar instalación de panel:
- `php artisan filament:install --panels`

3. Configurar `AdminPanelProvider`:
- `id('admin')`, `path('admin')`, `login()`
- `brandName`, `colors`, `favicon`
- `navigationGroups` iniciales

4. Configurar Shield:
- Publicar config
- `shield:install`
- `shield:generate --all`

5. Migrar y seed inicial:
- migraciones de permisos/roles
- usuario admin inicial

Resultado esperado:

- `/admin` accesible.
- login funcional.
- admin con permisos base.

### 8.2 Plan de Seguridad y Acceso

Objetivo: garantizar acceso solo para usuarios administrativos.

Roles iniciales:

- `admin`: gestión completa de panel.
- `reporte`: solo lectura de candidatos/resultados + exportación.

Permisos mínimos:

- `panel.access`
- `candidates.view`
- `results.view`
- `results.export`
- `users.manage` (solo admin)

Reglas:

- Todo acceso al panel pasa por auth + permiso.
- Recursos sensibles requieren policy explícita.
- Acciones de exportación restringidas por permiso.

Resultado esperado:

- matriz rol-permiso implementada y validada.

### 8.3 Plan de Implementación Funcional por Entregable

Objetivo: ejecutar desarrollo en orden de valor.

Orden recomendado:

1. `CandidateResource` (listado + filtros + tabs + ver resultado).
2. `TestResultResource` (listado + filtros + infolist).
3. Exportación Excel desde tabla de resultados.
4. Exportación PDF por resultado.
5. Recurso de usuarios admin y asignación de roles.
6. Dashboard inicial.

Criterio por entregable:

- incluye policy/permisos.
- incluye validación visual mínima.
- incluye prueba feature de acceso.

### 8.4 Plan de QA y Validación

Objetivo: asegurar que el panel sea usable y seguro antes de cierre.

Pruebas mínimas:

1. Acceso:
- usuario sin rol no entra a `/admin`.
- `reporte` no gestiona usuarios.

2. Candidatos:
- filtros funcionan (edad, educación, estado).
- tabs muestran conteos correctos.

3. Resultados:
- filtros por puntaje/validez/fecha correctos.
- botón PDF responde con archivo válido.

4. Exportes:
- Excel respeta filtros activos.
- columnas y formatos esperados.

Resultado esperado:

- suite mínima en verde para flujos críticos.

### 8.5 Plan de Salida a Producción

Objetivo: desplegar con bajo riesgo.

Pasos:

1. Congelar permisos/roles en seeder versionado.
2. Ejecutar migraciones en staging.
3. Validar smoke test de panel:
- login
- listado candidatos
- listado resultados
- exportación Excel/PDF
4. Deploy a producción en ventana controlada.
5. Monitoreo 24-48h:
- errores de auth
- tiempos de respuesta de listados
- fallos de exportación

Resultado esperado:

- panel estable en producción con operación básica garantizada.

## 9. Comandos Base de Ejecución (Inicio)

```bash
composer require filament/filament:"^4.0" -W
php artisan filament:install --panels

composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

composer require bezhansalleh/filament-shield
php artisan vendor:publish --tag=filament-shield-config
php artisan shield:install --fresh
php artisan shield:generate --all

php artisan migrate
php artisan make:filament-resource Candidate
php artisan make:filament-resource TestResult
```

## 10. Responsables Sugeridos

- Backend Lead: panel, recursos y policies.
- QA: pruebas de acceso, filtros y exportaciones.
- Product/Operación: validación funcional de pantallas y navegación.

## 11. Definición de Inicio (Ready to Start)

El equipo puede arrancar cuando:

1. Existe acuerdo sobre alcance reducido (solo admin consulta resultados).
2. Se aprueba matriz inicial de roles/permisos.
3. Se definen campos finales visibles en tablas de candidatos/resultados.
4. Se confirma formato requerido de reporte PDF.
