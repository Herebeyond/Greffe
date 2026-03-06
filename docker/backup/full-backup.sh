#!/bin/bash
# Full PostgreSQL backup script using pg_basebackup
# This creates a base backup that can be combined with WAL archives for point-in-time recovery

set -e

# Configuration
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
FULL_BACKUP_DIR="${BACKUP_DIR}/full"
WAL_ARCHIVE_DIR="${BACKUP_DIR}/wal_archive"
BACKUP_NAME="base_backup_${DATE}"
RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-7}

# Ensure directories exist
mkdir -p "${FULL_BACKUP_DIR}"
mkdir -p "${WAL_ARCHIVE_DIR}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting full backup: ${BACKUP_NAME}"

# Create base backup
pg_basebackup \
    -h database \
    -U "${POSTGRES_USER:-app}" \
    -D "${FULL_BACKUP_DIR}/${BACKUP_NAME}" \
    -Ft \
    -z \
    -Xs \
    -P \
    -v

# Create backup info file
cat > "${FULL_BACKUP_DIR}/${BACKUP_NAME}/backup_info.txt" << EOF
Backup Name: ${BACKUP_NAME}
Backup Date: $(date '+%Y-%m-%d %H:%M:%S')
Database: ${POSTGRES_DB:-app}
User: ${POSTGRES_USER:-app}
Type: Full (pg_basebackup)
Compression: gzip
EOF

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Full backup completed: ${BACKUP_NAME}"

# Cleanup old backups
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Cleaning up backups older than ${RETENTION_DAYS} days..."
find "${FULL_BACKUP_DIR}" -type d -name "base_backup_*" -mtime +${RETENTION_DAYS} -exec rm -rf {} + 2>/dev/null || true

# Count remaining backups
BACKUP_COUNT=$(ls -d "${FULL_BACKUP_DIR}"/base_backup_* 2>/dev/null | wc -l)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Current backup count: ${BACKUP_COUNT}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup process completed successfully"
