#!/usr/bin/env bash
#
# Backup and restore drill for the shop's PostgreSQL database.
#
# Takes a logical backup of SOURCE, restores it into a separate, disposable
# RESTORE database, and checks that every table's row count matches. Run it
# before launch and then regularly (e.g. monthly), so you know backups can be
# restored before you need one. The dump file is kept as an off-provider copy.
#
# Usage:
#   SOURCE_DATABASE_URL=postgres://user:pass@host:5432/shop \
#   RESTORE_DATABASE_URL=postgres://user:pass@host:5432/shop_restore_drill \
#   scripts/backup-restore-drill.sh [backup-dir]
#
# RESTORE_DATABASE_URL must point at an existing, EMPTY or disposable database:
# its contents are replaced. Never point it at production.
#
# Requires pg_dump, pg_restore and psql matching the server's major version.

set -euo pipefail

: "${SOURCE_DATABASE_URL:?Set SOURCE_DATABASE_URL}"
: "${RESTORE_DATABASE_URL:?Set RESTORE_DATABASE_URL}"

if [[ "$SOURCE_DATABASE_URL" == "$RESTORE_DATABASE_URL" ]]; then
  echo "Refusing to run: SOURCE and RESTORE are the same database." >&2
  exit 1
fi

backup_dir="${1:-storage/backups}"
mkdir -p "$backup_dir"
dump="$backup_dir/shop-$(date -u +%Y%m%dT%H%M%SZ).dump"

echo "1/3 Backing up source to $dump"
pg_dump --format=custom --no-owner --no-privileges --dbname="$SOURCE_DATABASE_URL" --file="$dump"

echo "2/3 Restoring into the drill database"
pg_restore --clean --if-exists --no-owner --no-privileges --exit-on-error \
  --dbname="$RESTORE_DATABASE_URL" "$dump"

echo "3/3 Comparing row counts"
# Exact counts for every table in the public schema, one "table|count" per line.
count_rows() {
  psql "$1" --no-psqlrc --tuples-only --no-align --quiet <<'SQL'
SELECT format('SELECT %L || ''|'' || count(*) FROM public.%I;', tablename, tablename)
FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename
\gexec
SQL
}

source_counts="$(count_rows "$SOURCE_DATABASE_URL")"
restore_counts="$(count_rows "$RESTORE_DATABASE_URL")"

if [[ "$source_counts" != "$restore_counts" ]]; then
  echo "FAILED: row counts differ between source and restore." >&2
  diff <(echo "$source_counts") <(echo "$restore_counts") >&2 || true
  exit 1
fi

tables=$(echo "$source_counts" | grep -c '|')
orders=$(echo "$source_counts" | awk -F'|' '$1 == "orders" { print $2 }')
echo "OK: $tables tables restored with matching row counts (orders: ${orders:-0})."
echo "Backup kept at $dump"
