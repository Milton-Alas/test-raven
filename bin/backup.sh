#!/usr/bin/env bash
#
# Respaldo completo: base de datos + clave de cifrado.
#
# Por qué juntos: la clave (`APP_KEY`) cifra el DUI/NIT de los candidatos. Un
# volcado de la base SIN la clave es irrecuperable, porque los identificadores
# quedan cifrados con una clave que ya no existe y los índices de búsqueda
# (`dui_nit_hash`) dejan de coincidir. Este script garantiza que ambos se generen
# en la misma ejecución y queden identificados con la misma marca de tiempo.
#
# Uso:
#   bash bin/backup.sh                    # respalda en storage/backups
#   bash bin/backup.sh /ruta/externa      # respalda en otra carpeta (recomendado:
#                                         # fuera del servidor o en un volumen distinto)
#
# Programación sugerida (crontab):
#   30 2 * * * cd /ruta/al/proyecto && bash bin/backup.sh /mnt/respaldos >> storage/logs/backup.log 2>&1
#
# Restauración: ver docs/RESPALDO_Y_OPERACION.md

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

DESTINO="${1:-$PROJECT_ROOT/storage/backups}"
MARCA="$(date +%Y-%m-%d_%H%M%S)"

mkdir -p "$DESTINO"
chmod 700 "$DESTINO" 2>/dev/null || true

# ---------------------------------------------------------------------------
# 1. Datos de conexión, leídos del .env sin ejecutar PHP
# ---------------------------------------------------------------------------
leer_env() {
    grep -E "^${1}=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' || true
}

DB_CONNECTION="$(leer_env DB_CONNECTION)"
DB_HOST="$(leer_env DB_HOST)"
DB_PORT="$(leer_env DB_PORT)"
DB_DATABASE="$(leer_env DB_DATABASE)"
DB_USERNAME="$(leer_env DB_USERNAME)"
DB_PASSWORD="$(leer_env DB_PASSWORD)"

echo "[$(date '+%F %T')] Iniciando respaldo de ${DB_DATABASE:-?} en ${DESTINO}"

# ---------------------------------------------------------------------------
# 2. Volcado de la base
# ---------------------------------------------------------------------------
if [ "$DB_CONNECTION" = "mysql" ]; then
    VOLCADO="${DESTINO}/db-${DB_DATABASE}-${MARCA}.sql.gz"

    # --single-transaction: consistencia sin bloquear la tabla durante el volcado.
    # --routines --triggers --events: incluye la lógica almacenada en el servidor.
    MYSQL_PWD="$DB_PASSWORD" mysqldump \
        --host="$DB_HOST" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --default-character-set=utf8mb4 \
        "$DB_DATABASE" | gzip > "$VOLCADO"

    chmod 600 "$VOLCADO" 2>/dev/null || true
    echo "[$(date '+%F %T')] Base volcada: $(basename "$VOLCADO") ($(du -h "$VOLCADO" | cut -f1))"
elif [ "$DB_CONNECTION" = "sqlite" ]; then
    ORIGEN="$DB_DATABASE"
    [ "${ORIGEN:0:1}" != "/" ] && ORIGEN="$PROJECT_ROOT/$ORIGEN"

    VOLCADO="${DESTINO}/db-sqlite-${MARCA}.sqlite.gz"
    gzip -c "$ORIGEN" > "$VOLCADO"

    chmod 600 "$VOLCADO" 2>/dev/null || true
    echo "[$(date '+%F %T')] Base volcada: $(basename "$VOLCADO")"
else
    echo "[$(date '+%F %T')] ERROR: DB_CONNECTION='${DB_CONNECTION}' no soportado por este script." >&2
    exit 1
fi

# ---------------------------------------------------------------------------
# 3. Clave de cifrado (sin ella el volcado es inservible)
# ---------------------------------------------------------------------------
CLAVE="${DESTINO}/app-key-${MARCA}.txt"

php artisan app:backup-encryption-key --path="$DESTINO" --force > /dev/null

# El comando nombra el archivo por día; se renombra con la misma marca que el
# volcado para que ambos archivos se emparejen sin ambigüedad.
CLAVE_DIARIA="${DESTINO}/app-key-$(date +%Y-%m-%d).txt"
if [ -f "$CLAVE_DIARIA" ]; then
    mv "$CLAVE_DIARIA" "$CLAVE"
elif [ ! -f "$CLAVE" ]; then
    # El comando pudo haber escrito con otro nombre: se toma el más reciente.
    ULTIMA="$(find "$DESTINO" -maxdepth 1 -name 'app-key-*.txt' -type f -newermt '-1 minute' 2>/dev/null | head -1)"
    [ -n "$ULTIMA" ] && mv "$ULTIMA" "$CLAVE"
fi
chmod 600 "$CLAVE" 2>/dev/null || true

if [ ! -s "$CLAVE" ]; then
    echo "[$(date '+%F %T')] ERROR: no se pudo respaldar la clave de cifrado." >&2
    exit 1
fi

echo "[$(date '+%F %T')] Clave respaldada: $(basename "$CLAVE") (permisos 600)"

# ---------------------------------------------------------------------------
# 4. Manifiesto: deja constancia de qué se respaldó y con qué versión del código
# ---------------------------------------------------------------------------
MANIFIESTO="${DESTINO}/manifiesto-${MARCA}.txt"
{
    echo "Respaldo del Test de Raven"
    echo "Fecha:            $(date '+%F %T')"
    echo "Base de datos:    ${DB_DATABASE}"
    echo "Volcado:          $(basename "$VOLCADO")"
    echo "Clave de cifrado: $(basename "$CLAVE")  (RESTRINGIDA: permite descifrar DUI/NIT)"
    echo "Commit:           $(git rev-parse HEAD 2>/dev/null || echo 'sin git')"
    echo "Rama:             $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '-')"
    echo "PHP:              $(php -r 'echo PHP_VERSION;' 2>/dev/null || echo '-')"
    echo
    echo "RESTAURACIÓN: ambos archivos son necesarios. Ver docs/RESPALDO_Y_OPERACION.md"
} > "$MANIFIESTO"
chmod 600 "$MANIFIESTO" 2>/dev/null || true

echo "[$(date '+%F %T')] Manifiesto: $(basename "$MANIFIESTO")"

# ---------------------------------------------------------------------------
# 5. Retención de los propios respaldos (por defecto 30 días)
# ---------------------------------------------------------------------------
DIAS="${RESPALDO_RETENCION_DIAS:-30}"
find "$DESTINO" \( -name 'db-*.sql.gz' -o -name 'db-sqlite-*.gz' -o -name 'app-key-*.txt' -o -name 'manifiesto-*.txt' \) \
    -type f -mtime "+${DIAS}" -print -delete 2>/dev/null | while read -r borrado; do
        echo "[$(date '+%F %T')] Respaldo antiguo eliminado (>${DIAS} días): $(basename "$borrado")"
    done

echo "[$(date '+%F %T')] Respaldo completado."

# ---------------------------------------------------------------------------
# Advertencia final: los dos archivos juntos son equivalentes a los datos en claro
# ---------------------------------------------------------------------------
echo
echo "IMPORTANTE: guarda el archivo app-key-*.txt SEPARADO del volcado de la base."
echo "Juntos permiten descifrar los identificadores de los candidatos."
