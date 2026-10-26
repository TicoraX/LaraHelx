#!/bin/sh
# Rol de la app sin SUPERUSER, CREATEDB ni CREATEROLE, dueño de la base (05 §1).
# Solo corre con el volumen de datos vacío.
set -eu
psql -v ON_ERROR_STOP=1 -v pw="$APP_DB_PASSWORD" -U "$POSTGRES_USER" -d "$POSTGRES_DB" <<'SQL'
CREATE ROLE app LOGIN PASSWORD :'pw';
ALTER DATABASE laravel OWNER TO app;
SQL
