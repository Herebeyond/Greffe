#!/bin/bash
# Incremental backup script - copies WAL files from PostgreSQL
# WAL files contain all changes since the last backup, enabling point-in-time recovery

set -e

# Configuration
BACKUP_DIR="/backups"
WAL_SOURCE="/wal_archive"
WAL_DEST="${BACKUP_DIR}/wal_archive"
RETENTION_DAYS=${WAL_RETENTION_DAYS:-3}

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting incremental WAL backup..."

# Ensure destination exists
mkdir -p "${WAL_DEST}"

# Count files before sync
BEFORE_COUNT=$(ls -1 "${WAL_DEST}" 2>/dev/null | wc -l)

# Sync WAL files (incremental - only new/changed files)
rsync -av --ignore-existing "${WAL_SOURCE}/" "${WAL_DEST}/"

# Count files after sync
AFTER_COUNT=$(ls -1 "${WAL_DEST}" 2>/dev/null | wc -l)
NEW_FILES=$((AFTER_COUNT - BEFORE_COUNT))

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Synced ${NEW_FILES} new WAL files"

# Cleanup old WAL files (keep files newer than retention period)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Cleaning up WAL files older than ${RETENTION_DAYS} days..."
find "${WAL_DEST}" -type f -mtime +${RETENTION_DAYS} -delete 2>/dev/null || true

# Report status
TOTAL_FILES=$(ls -1 "${WAL_DEST}" 2>/dev/null | wc -l)
TOTAL_SIZE=$(du -sh "${WAL_DEST}" 2>/dev/null | cut -f1)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] WAL archive: ${TOTAL_FILES} files, ${TOTAL_SIZE}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Incremental backup completed"
