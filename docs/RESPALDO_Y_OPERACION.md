# Respaldo y operación

Guía operativa del sistema: comandos manuales, respaldo (incluida la clave de cifrado) y tareas
programadas. Está escrita para quien administra el servidor, no para quien desarrolla.

---

## 1. Comandos manuales

Todos se ejecutan desde la raíz del proyecto.

### Retención de datos (RNF-09.04 / RNF-09.05)

```bash
# Ver qué haría, SIN modificar nada. Es lo primero que conviene ejecutar.
php artisan retention:apply --dry-run

# Aplicar todas las políticas
php artisan retention:apply

# Aplicar una sola categoría
php artisan retention:apply --categoria=personal
php artisan retention:apply --categoria=psicometrico
php artisan retention:apply --categoria=actividad
php artisan retention:apply --categoria=tecnico

# Ejecutar aunque la retención esté desactivada en el entorno
php artisan retention:apply --force

# Combinables: simular solo una categoría
php artisan retention:apply --categoria=tecnico --dry-run
```

**Qué hace cada categoría**

| Categoría | Alcanza | Acción por defecto | Plazo por defecto |
| --- | --- | --- | --- |
| `personal` | Nombre, correo, DUI/NIT, edad, ocupación, nivel educativo | disociar | 1825 días |
| `psicometrico` | Resultados, puntajes, percentiles, respuestas | disociar | 1825 días |
| `actividad` | Registros de auditoría | suprimir | 730 días |
| `tecnico` | IP, user agent, datos del navegador | suprimir | 180 días |

**Aclaración importante sobre `--dry-run`:** recorre exactamente la misma lógica que la ejecución
real, pero no escribe cambios. **Sí deja registro** en `retention_logs` con `simulacion = true`, para
poder demostrar que la política se evaluó sin aplicarla.

**Cuándo se ejecuta sola (periodicidad configurable)**

El planificador aplica la política con la frecuencia que fije el entorno, sin tocar código:

```env
RETENCION_SCHEDULE_ACTIVA=true          # false deja solo la ejecución manual
RETENCION_SCHEDULE_FRECUENCIA=daily     # daily | weekly | monthly | quarterly | yearly
RETENCION_SCHEDULE_HORA=03:00
RETENCION_SCHEDULE_DIA_SEMANA=1         # solo con weekly (1 = lunes)
RETENCION_SCHEDULE_DIA_MES=1            # con monthly, quarterly y yearly
RETENCION_SCHEDULE_MES=1                # solo con yearly
RETENCION_SCHEDULE_SIMULACION=false     # true: la tarea programada ejecuta --dry-run
```

El ciclo del proceso es el año escolar —los aspirantes a profesorado rinden el test una vez por año—,
así que una configuración razonable para producción es anual, después del cierre de la convocatoria:

```env
RETENCION_SCHEDULE_FRECUENCIA=yearly
RETENCION_SCHEDULE_MES=12
RETENCION_SCHEDULE_DIA_MES=15
RETENCION_SCHEDULE_HORA=02:00
```

Para desplegar en staging y ver el alcance real antes de activarla, `RETENCION_SCHEDULE_SIMULACION=true`
deja la tarea programada en modo `--dry-run`. Los cambios en estas variables se aplican en el siguiente
`schedule:run`, sin volver a desplegar.

En `retention_logs`, el campo `origen` distingue la ejecución automática (`schedule`, la que lanza el
planificador) de la manual (`manual`) y `simulacion` marca las que no modificaron nada.

### Respaldo de la clave de cifrado

```bash
# Guarda la APP_KEY en storage/backups con permisos 600
php artisan app:backup-encryption-key

# En otra carpeta (recomendado: volumen distinto al de la base)
php artisan app:backup-encryption-key --path=/mnt/respaldos

# Sobrescribir el respaldo del día
php artisan app:backup-encryption-key --force
```

### Respaldo completo (base + clave)

```bash
# A storage/backups
bash bin/backup.sh

# A una ruta externa (recomendado)
bash bin/backup.sh /mnt/respaldos
```

Genera tres archivos con la misma marca de tiempo, todos con permisos `600`:

| Archivo | Contenido |
| --- | --- |
| `db-<base>-<marca>.sql.gz` | Volcado comprimido de la base |
| `app-key-<marca>.txt` | `APP_KEY` (y `APP_PREVIOUS_KEYS` si existen) |
| `manifiesto-<marca>.txt` | Qué se respaldó, con commit, rama y versión de PHP |

Conserva 30 días por defecto (`RESPALDO_RETENCION_DIAS` para cambiarlo).

### Consultar el histórico de retención

```bash
php artisan tinker --execute="App\Models\RetentionLog::latest()->take(10)->get(['categoria','accion','registros_afectados','resultado','simulacion','created_at'])"
```

### Otras operaciones útiles

```bash
php artisan migrate --force          # aplicar migraciones (incluye el cifrado del DUI/NIT)
php artisan db:seed --class=RavenTestSeeder --force   # cargar el banco de reactivos
php artisan storage:link             # crear el enlace public/storage
php artisan schedule:list            # ver las tareas programadas
php artisan test                     # suite completa
php artisan test --filter=Rnf09      # solo las pruebas de confidencialidad
```

---

## 2. La clave de cifrado en cada respaldo

Esta sección es la más importante de la guía. **Un respaldo de la base sin la `APP_KEY` es
inservible.**

### Por qué

La `APP_KEY` cifra el `dui_nit` de cada candidato (RNF-09.01). Si se pierde:

- Los identificadores almacenados son **irrecuperables**: no hay forma de descifrarlos.
- Los índices `dui_nit_hash` dejan de coincidir con los valores recalculados, así que **nadie podría
  iniciar sesión por DUI** (solo por correo).

Por eso el respaldo de la base y el de la clave se generan **juntos**, en la misma ejecución y con la
misma marca de tiempo, y por eso quedan emparejados por nombre:

```
db-test_raven-2026-09-19_180418.sql.gz
app-key-2026-09-19_180418.txt          <- sin este archivo, el de arriba no sirve
manifiesto-2026-09-19_180418.txt
```

### Cómo se guarda

`bin/backup.sh` ejecuta `php artisan app:backup-encryption-key`, que:

1. Extrae **solo** la `APP_KEY` (y las claves previas si las hay). No copia el `.env`, porque el
   `.env` contiene además credenciales de base de datos y correo que no hacen falta para recuperar
   los datos cifrados.
2. Escribe un archivo con instrucciones de restauración y rotación incluidas.
3. Aplica permisos `600` (solo el propietario puede leerlo). El propio volcado de la base también
   queda en `600`, porque contiene datos personales.

### Regla de custodia

**Los dos archivos juntos son equivalentes a los datos en claro.** Por lo tanto:

- Guárdalos **separados**: la clave en un gestor de secretos o un volumen distinto del volcado.
- No subas ninguno de los dos al repositorio (ya están en `.gitignore`).
- No los envíes por correo ni por mensajería sin cifrar.

### Rotación de la clave

Si necesitas cambiar la `APP_KEY` (por ejemplo, ante sospecha de compromiso):

1. Genera una nueva y **conserva la anterior**.
2. Deja la nueva en `APP_KEY` y la anterior en `APP_PREVIOUS_KEYS` (separadas por coma para varias):
   ```env
   APP_KEY=base64:NUEVA...
   APP_PREVIOUS_KEYS=base64:ANTERIOR...
   ```
3. Con eso, Laravel puede seguir **leyendo** los datos cifrados con la clave anterior mientras todo se
   re-cifra. El comando de respaldo incluye las claves previas en el archivo.
4. Re-cifra los datos existentes con un script de re-guardado y, cuando ya no quede nada con la clave
   vieja, retírala de `APP_PREVIOUS_KEYS`.

**Nunca cambies la `APP_KEY` sin conservar la anterior**: los DUI/NIT existentes quedarían
irrecuperables en el mismo instante.

### Verificar que un respaldo es restaurable

Un respaldo que nunca se probó no es un respaldo. Prueba periódicamente contra una base desechable:

```bash
# 1. Crear una base de prueba
mysql -h 127.0.0.1 -u root -p -e "DROP DATABASE IF EXISTS raven_restore_test; CREATE DATABASE raven_restore_test CHARACTER SET utf8mb4;"

# 2. Restaurar el volcado
zcat storage/backups/db-test_raven-<marca>.sql.gz | mysql -h 127.0.0.1 -u root -p raven_restore_test

# 3. Comprobar que la clave respaldada descifra los datos
APP_KEY="$(grep '^APP_KEY=' storage/backups/app-key-<marca>.txt | cut -d= -f2-)" \
DB_DATABASE=raven_restore_test \
php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = Illuminate\Support\Facades\DB::table("candidates")->first();
echo $c ? App\Support\DuiNitCipher::decrypt($c->dui_nit) : "sin candidatos", PHP_EOL;'

# 4. Limpiar
mysql -h 127.0.0.1 -u root -p -e "DROP DATABASE raven_restore_test;"
```

El paso 3 es el que importa: si devuelve el DUI en claro, el par volcado + clave es válido. Si
devuelve vacío o un error, el respaldo no sirve para recuperar los datos.

### Restauración completa en un servidor nuevo

1. Desplegar el código en el commit indicado en el manifiesto.
2. Crear la base y restaurar el volcado (paso 2 de arriba).
3. Colocar la `APP_KEY` del respaldo en el `.env`. **Sin esto, no continúes**: no tiene sentido
   arrancar con los datos cifrados y sin la clave.
4. `php artisan storage:link` y verificar las láminas.
5. `php artisan config:clear && php artisan cache:clear`.
6. Comprobar en el panel que un candidato muestra su DUI (enmascarado en el listado, completo en la
   ficha). Si el DUI aparece vacío, la clave no es la correcta.

### Lo que la retención NO alcanza

Los respaldos siguen su propio ciclo. Si la retención disocia un candidato a los 5 años, **un
respaldo anterior conserva sus datos personales** hasta que ese respaldo se elimine. Es una decisión
que la institución debe asumir y declarar: la política se aplica sobre la base activa.

---

## 3. Cron sugerido

El servidor debe ejecutar el planificador de Laravel cada minuto. Es lo que activa la retención
automática (RNF-09.05) y cualquier tarea futura.

```cron
# /etc/cron.d/test-raven  (o la crontab del usuario del servicio)
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MAILTO=ti@ues.edu.sv

# Planificador de Laravel: obligatorio. Activa la retención automática con la
# periodicidad configurada en RETENCION_SCHEDULE_* (por defecto, 03:00).
* * * * * www-data cd /var/www/test-raven && php artisan schedule:run >> /dev/null 2>&1

# Respaldo diario (base + clave) a un volumen externo, 02:30
30 2 * * * www-data cd /var/www/test-raven && bash bin/backup.sh /mnt/respaldos >> storage/logs/backup.log 2>&1

# Rotación de los registros de la aplicación (Laravel no trae "log:clear";
# se configuran en config/logging.php con "days", o se recortan con logrotate).
# 0 4 * * 0 root /usr/sbin/logrotate /etc/logrotate.d/test-raven
```

**Notas sobre el cron**

- **La línea del planificador es la única obligatoria.** Sin ella, la retención no se aplica sola; el
  comando manual sigue disponible.
- El respaldo va **a un volumen distinto** del que usa la base. Un respaldo en el mismo disco no
  protege ante fallo del disco.
- `MAILTO` hace que cualquier error del cron llegue por correo. Conviene dejarlo configurado.
- Si el entorno contenerizado no permite cron, la alternativa es un *sidecar* que ejecute
  `php artisan schedule:work`, o un cron del host que haga `docker compose exec app php artisan schedule:run`.

### Verificar que el cron está funcionando

```bash
php artisan schedule:list          # debe mostrar retention:apply en la frecuencia y hora configuradas
php artisan schedule:run           # ejecuta lo que esté pendiente ahora
```

Y cuando corresponda la ejecución, comprobar que quedó registro:

```bash
php artisan tinker --execute="App\Models\RetentionLog::where('origen','schedule')->latest()->first()"
```

Si devuelve `null` una vez pasada la hora configurada (`RETENCION_SCHEDULE_HORA`), el cron no está
corriendo. Ojo con la frecuencia: con `monthly`, `quarterly` o `yearly` el registro aparece solo en la
fecha programada, así que la comprobación se hace ese día.

---

## 4. Contexto: qué se activa con qué

| Requisito | Qué lo activa | Cómo se demuestra |
| --- | --- | --- |
| RNF-09.01 Cifrado | `php artisan migrate` | `php artisan test --filter=Rnf0901` |
| RNF-09.02 Credenciales | Ya activo (bcrypt + reseteo por admin) | `php artisan test --filter=CandidatePasswordReset` |
| RNF-09.03 Separación de acceso | Ya activo (guards + roles) | `php artisan test --filter=Rnf0903` |
| RNF-09.04 Retención | Configuración en `.env` / `config/retention.php` | `php artisan retention:apply --dry-run` |
| RNF-09.05 Automatización | **Cron del planificador** | `retention_logs` con `origen = schedule` |

El detalle de cumplimiento está en [`RNF-09_CONFIDENCIALIDAD.md`](RNF-09_CONFIDENCIALIDAD.md).
