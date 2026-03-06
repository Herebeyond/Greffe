#!/bin/bash
# PostgreSQL initialization script for backup directories
# This script runs on first container initialization

set -e

# Create WAL archive directory
mkdir -p /var/lib/postgresql/wal_archive
chown postgres:postgres /var/lib/postgresql/wal_archive
chmod 700 /var/lib/postgresql/wal_archive

echo "Backup directories initialized successfully"
