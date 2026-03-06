#!/bin/bash
# PostgreSQL restore script for point-in-time recovery
# Restores from a base backup and replays WAL files up to a specific point

set -e

# Configuration
BACKUP_DIR="/backups"
RESTORE_DIR="/restore"
PGDATA="/var/lib/postgresql/data"

# Parse arguments
BACKUP_NAME="${1:-}"
RECOVERY_TARGET="${2:-}"  # Optional: timestamp like '2026-03-06 14:30:00'

show_usage() {
    echo "Usage: $0 <backup_name> [recovery_target_time]"
    echo ""
    echo "Arguments:"
    echo "  backup_name         Name of the base backup to restore (e.g., base_backup_20260306_140000)"
    echo "  recovery_target_time  Optional: Point-in-time to recover to (e.g., '2026-03-06 14:30:00')"
    echo ""
    echo "Available backups:"
    ls -1 "${BACKUP_DIR}/full/" 2>/dev/null || echo "  No backups found"
    exit 1
}

if [ -z "${BACKUP_NAME}" ]; then
    show_usage
fi

BACKUP_PATH="${BACKUP_DIR}/full/${BACKUP_NAME}"

if [ ! -d "${BACKUP_PATH}" ]; then
    echo "ERROR: Backup not found: ${BACKUP_PATH}"
    show_usage
fi

echo "=============================================="
echo "PostgreSQL Point-in-Time Recovery"
echo "=============================================="
echo "Base Backup: ${BACKUP_NAME}"
echo "Recovery Target: ${RECOVERY_TARGET:-latest}"
echo ""

# Confirm before proceeding
read -p "This will REPLACE the current database. Continue? (yes/no): " CONFIRM
if [ "${CONFIRM}" != "yes" ]; then
    echo "Restore cancelled."
    exit 1
fi

echo ""
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting restore process..."

# Create restore directory
mkdir -p "${RESTORE_DIR}"
cd "${RESTORE_DIR}"

# Extract base backup
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Extracting base backup..."
tar -xzf "${BACKUP_PATH}/base.tar.gz" -C "${RESTORE_DIR}"

# Copy WAL files for recovery
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Copying WAL archive..."
mkdir -p "${RESTORE_DIR}/pg_wal"
cp "${BACKUP_DIR}/wal_archive/"* "${RESTORE_DIR}/pg_wal/" 2>/dev/null || true

# Create recovery configuration
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Creating recovery configuration..."

cat > "${RESTORE_DIR}/postgresql.auto.conf" << EOF
# Recovery configuration
restore_command = 'cp ${BACKUP_DIR}/wal_archive/%f %p'
EOF

if [ -n "${RECOVERY_TARGET}" ]; then
    cat >> "${RESTORE_DIR}/postgresql.auto.conf" << EOF
recovery_target_time = '${RECOVERY_TARGET}'
recovery_target_action = 'promote'
EOF
fi

# Create recovery signal file
touch "${RESTORE_DIR}/recovery.signal"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Restore preparation completed."
echo ""
echo "=============================================="
echo "MANUAL STEPS REQUIRED:"
echo "=============================================="
echo "1. Stop the database container:"
echo "   docker compose stop database"
echo ""
echo "2. Replace the data directory:"
echo "   docker compose run --rm -v ${RESTORE_DIR}:/restore database sh -c 'rm -rf /var/lib/postgresql/data/* && cp -r /restore/* /var/lib/postgresql/data/'"
echo ""
echo "3. Start the database:"
echo "   docker compose start database"
echo ""
echo "4. Monitor recovery in logs:"
echo "   docker compose logs -f database"
echo "=============================================="
