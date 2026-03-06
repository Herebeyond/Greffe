# Database Backup System

This document describes the incremental backup system for the PostgreSQL database.

---

## Overview

The backup system uses PostgreSQL's **Write-Ahead Logging (WAL)** for incremental backups, combined with periodic full backups using `pg_basebackup`. This approach provides:

- **Point-in-Time Recovery (PITR)**: Restore to any moment in time
- **Minimal Storage**: Only changes are stored between full backups
- **Low Performance Impact**: WAL archiving is built into PostgreSQL
- **Automated Scheduling**: Full backups daily, incremental hourly

---

## Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   PostgreSQL    │────►│   WAL Archive   │────►│  Backup Service │
│    Database     │     │    (Volume)     │     │   (Container)   │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                        │
                                                        ▼
                                                ┌─────────────────┐
                                                │    Backups      │
                                                │    (Volume)     │
                                                │  - Full backups │
                                                │  - WAL archive  │
                                                └─────────────────┘
```

---

## Backup Types

### Full Backup (Daily)

- **Schedule**: Daily at 2:00 AM
- **Method**: `pg_basebackup` with compression
- **Retention**: 7 days (configurable)
- **Contents**: Complete database snapshot

### Incremental Backup (Hourly)

- **Schedule**: Every hour (configurable)
- **Method**: WAL file synchronization
- **Retention**: 3 days (configurable)
- **Contents**: All database changes since last backup

---

## Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `BACKUP_RETENTION_DAYS` | 7 | Days to keep full backups |
| `WAL_RETENTION_DAYS` | 3 | Days to keep WAL archive files |
| `INCREMENTAL_INTERVAL` | 3600 | Seconds between incremental backups |

### Files

| File | Purpose |
|------|---------|
| `docker/postgres/postgresql.conf` | PostgreSQL WAL archiving configuration |
| `docker/postgres/init-backup-dirs.sh` | Initializes backup directories |
| `docker/backup/Dockerfile` | Backup service container |
| `docker/backup/full-backup.sh` | Full backup script |
| `docker/backup/incremental-backup.sh` | Incremental backup script |
| `docker/backup/restore.sh` | Point-in-time recovery script |
| `docker/backup/scheduler.sh` | Backup scheduling daemon |

### Docker Volumes

| Volume | Purpose |
|--------|---------|
| `database_data` | PostgreSQL data directory |
| `wal_archive` | WAL files for incremental backups |
| `backups` | Full backups and archived WAL files |

---

## Commands

### Start the Backup Service

```bash
# Start all services including backup
docker compose up -d

# Or start just the backup service
docker compose up -d backup
```

### View Backup Logs

```bash
# Follow backup service logs
docker compose logs -f backup

# View backup log file
docker compose exec backup cat /backups/backup.log
```

### Manual Full Backup

```bash
docker compose exec backup /scripts/full-backup.sh
```

### Manual Incremental Backup

```bash
docker compose exec backup /scripts/incremental-backup.sh
```

### List Available Backups

```bash
docker compose exec backup ls -la /backups/full/
```

### Check Backup Status

```bash
# See backup volume size
docker compose exec backup du -sh /backups/*

# Count WAL files
docker compose exec backup ls -1 /backups/wal_archive | wc -l
```

---

## Restore Procedures

### Restore to Latest State

```bash
# 1. List available backups
docker compose exec backup ls -la /backups/full/

# 2. Stop the database
docker compose stop database

# 3. Run the restore script (interactive)
docker compose exec backup /scripts/restore.sh base_backup_YYYYMMDD_HHMMSS

# 4. Follow the manual steps provided by the script
```

### Point-in-Time Recovery

Restore to a specific moment (e.g., just before an accidental deletion):

```bash
# Restore to March 6, 2026 at 14:30:00
docker compose exec backup /scripts/restore.sh base_backup_20260306_020000 '2026-03-06 14:30:00'
```

### Emergency Recovery Steps

If the database container is corrupted:

```bash
# 1. Stop all services
docker compose down

# 2. Remove corrupted data volume (CAUTION!)
docker volume rm grefferenale_database_data

# 3. Recreate the data volume
docker compose up -d database

# 4. Wait for initialization
docker compose logs -f database

# 5. Restore from backup
docker compose up -d backup
docker compose exec backup /scripts/restore.sh <latest_backup>
```

---

## Backup Verification

### Test Backup Integrity

```bash
# Check that the backup tar files are valid
docker compose exec backup tar -tzf /backups/full/base_backup_*/base.tar.gz | head

# Verify WAL files exist
docker compose exec backup ls -la /backups/wal_archive/
```

### Test Restore (Recommended Monthly)

1. Create a test environment
2. Restore the latest backup
3. Verify data integrity
4. Document any issues

---

## Monitoring

### Alerts to Configure

1. **Backup service down**: Container not running
2. **No recent full backup**: More than 25 hours since last full backup
3. **No recent WAL files**: No new WAL files in past 2 hours
4. **Disk space low**: Backup volume over 80% full

### Prometheus Metrics (If Applicable)

The backup scripts can be extended to export metrics:

- `backup_last_full_timestamp`
- `backup_last_incremental_timestamp`
- `backup_wal_file_count`
- `backup_total_size_bytes`

---

## Troubleshooting

### Backup Service Won't Start

```bash
# Check container logs
docker compose logs backup

# Verify database is healthy
docker compose ps database

# Manually test database connection
docker compose exec backup pg_isready -h database -U app
```

### WAL Files Not Being Archived

```bash
# Check PostgreSQL logs
docker compose logs database | grep -i wal

# Verify WAL archive directory
docker compose exec database ls -la /var/lib/postgresql/wal_archive/

# Check archive_command is working
docker compose exec database psql -U app -c "SHOW archive_command;"
```

### Full Backup Fails

```bash
# Check disk space
docker compose exec backup df -h /backups

# Verify pg_basebackup works
docker compose exec backup pg_basebackup -h database -U app -D /tmp/test_backup --checkpoint=fast
```

### Restore Fails

```bash
# Verify backup integrity
docker compose exec backup tar -tzf /backups/full/<backup>/base.tar.gz

# Check WAL files exist for the time period
docker compose exec backup ls -la /backups/wal_archive/

# Review PostgreSQL recovery documentation
```

---

## Security Considerations

1. **Encrypt backups at rest** in production (consider encrypting the backup volume)
2. **Restrict backup volume access** to authorized personnel only
3. **Store offsite copies** for disaster recovery
4. **Encrypt backup transfers** when copying to remote storage
5. **Test restores regularly** to ensure backups are valid

---

## Production Recommendations

### Offsite Backup

Add a script to copy backups to remote storage:

```bash
# Example: Copy to S3 (add to scheduler)
aws s3 sync /backups/full/ s3://my-bucket/greffe-renale/backups/full/
aws s3 sync /backups/wal_archive/ s3://my-bucket/greffe-renale/backups/wal/
```

### Backup Encryption

For HIPAA/HDS compliance, encrypt backups:

```bash
# Encrypt backup with GPG
gpg --encrypt --recipient backup@hospital.fr /backups/full/base_backup_*.tar.gz
```

### Monitoring Integration

Integrate with your monitoring system (Prometheus, Grafana, etc.) to:
- Track backup success/failure
- Monitor backup sizes
- Alert on missed backups

---

*Last updated: March 6, 2026*
