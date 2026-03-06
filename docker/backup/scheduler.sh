#!/bin/bash
# Backup scheduler - runs incremental backups frequently, full backups daily
# This script is designed to run continuously in a container

set -e

# Configuration
FULL_BACKUP_CRON="${FULL_BACKUP_CRON:-0 2 * * *}"        # Daily at 2 AM
INCREMENTAL_INTERVAL="${INCREMENTAL_INTERVAL:-3600}"     # Every hour (in seconds)
BACKUP_DIR="/backups"
LOG_FILE="${BACKUP_DIR}/backup.log"

# Ensure log directory exists
mkdir -p "${BACKUP_DIR}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "${LOG_FILE}"
}

log "Backup scheduler starting..."
log "Full backup schedule: ${FULL_BACKUP_CRON}"
log "Incremental backup interval: ${INCREMENTAL_INTERVAL} seconds"

# Track last full backup time
LAST_FULL_BACKUP=0

# Function to check if it's time for a full backup (simple daily check)
should_run_full_backup() {
    CURRENT_HOUR=$(date +%H)
    CURRENT_MINUTE=$(date +%M)
    CURRENT_DAY=$(date +%j)  # Day of year
    
    # Run at 2:00 AM if we haven't run today
    if [ "${CURRENT_HOUR}" = "02" ] && [ "${CURRENT_MINUTE}" -lt "05" ]; then
        if [ "${LAST_FULL_BACKUP}" != "${CURRENT_DAY}" ]; then
            return 0  # true
        fi
    fi
    return 1  # false
}

# Wait for database to be ready
log "Waiting for database to be ready..."
until pg_isready -h database -U "${POSTGRES_USER:-app}" -d "${POSTGRES_DB:-app}"; do
    log "Database not ready, waiting..."
    sleep 5
done
log "Database is ready"

# Initial full backup if none exists
if [ ! -d "${BACKUP_DIR}/full" ] || [ -z "$(ls -A ${BACKUP_DIR}/full 2>/dev/null)" ]; then
    log "No existing backups found, creating initial full backup..."
    /scripts/full-backup.sh 2>&1 | tee -a "${LOG_FILE}"
    LAST_FULL_BACKUP=$(date +%j)
fi

# Main loop
while true; do
    # Check for full backup
    if should_run_full_backup; then
        log "Starting scheduled full backup..."
        /scripts/full-backup.sh 2>&1 | tee -a "${LOG_FILE}" || log "Full backup failed!"
        LAST_FULL_BACKUP=$(date +%j)
    fi
    
    # Run incremental backup
    log "Starting incremental backup..."
    /scripts/incremental-backup.sh 2>&1 | tee -a "${LOG_FILE}" || log "Incremental backup failed!"
    
    # Sleep until next incremental
    log "Sleeping for ${INCREMENTAL_INTERVAL} seconds..."
    sleep "${INCREMENTAL_INTERVAL}"
done
