#!/bin/bash
# PostgreSQL initialization script for backup directories
# This script runs on first container initialization

set -e

# Create WAL archive directory (best-effort: the directory is a Docker volume
# already owned by postgres on most setups; chown may fail if mounted by root,
# which is fine because the bind mount already grants access).
mkdir -p /var/lib/postgresql/wal_archive || true
chown postgres:postgres /var/lib/postgresql/wal_archive 2>/dev/null || true
chmod 700 /var/lib/postgresql/wal_archive 2>/dev/null || true

echo "Backup directories initialized successfully"
