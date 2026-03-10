# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Added - 2026-03-06: Field-Level Encryption for Sensitive Medical Data

Implemented comprehensive field-level encryption using **paragonie/halite** (libsodium) to protect sensitive PII and medical data at rest.

#### New Files Created

| File | Purpose |
|------|---------|
| `src/Service/EncryptionService.php` | Core encryption/decryption service using Halite symmetric encryption |
| `src/Doctrine/Type/EncryptedStringType.php` | Custom Doctrine type that auto-encrypts on persist and decrypts on load |
| `src/Command/GenerateEncryptionKeyCommand.php` | Console command to generate encryption keys (`app:generate-encryption-key`) |
| `src/EventSubscriber/EncryptionInitializerSubscriber.php` | Initializes encryption service for Doctrine types on request/command |

#### Modified Files

| File | Changes |
|------|---------|
| `config/packages/doctrine.yaml` | Added `encrypted_string` custom type registration |
| `config/services.yaml` | Added encryption service configuration with key path parameter |
| `.env` | Added `ENCRYPTION_KEY_PATH` environment variable documentation |
| `.gitignore` | Added `*.key` and `encryption.key` to prevent key commits |
| `src/Entity/User.php` | Changed `name`, `surname`, `cristalId` to use `encrypted_string` type |
| `.github/copilot-instructions.md` | Documented encryption setup and usage |

#### Database Migration

- `migrations/Version20260306135612.php` - Converted encrypted columns from `VARCHAR` to `TEXT` (encrypted data is longer than plaintext)

#### Packages Installed

- `paragonie/halite` (v5.1.4) - High-level cryptography interface
- `paragonie/hidden-string` (v2.2.0) - Secure string encapsulation
- `paragonie/constant_time_encoding` (v3.1.3) - Timing-safe encoding
- `paragonie/sodium_compat` (v2.5.0) - Pure PHP libsodium fallback

#### How It Works

1. **Key Generation**: Run `bin/console app:generate-encryption-key` once per environment
2. **Key Storage**: Key saved to `var/encryption.key` (default) or path in `ENCRYPTION_KEY_PATH`
3. **Auto-Encryption**: Fields with `#[ORM\Column(type: 'encrypted_string')]` are automatically encrypted before database storage
4. **Auto-Decryption**: Data is transparently decrypted when loaded via Doctrine
5. **Lazy Loading**: Encryption key is only loaded when actually needed (not during cache:clear)

#### Security Notes

- Encryption key uses libsodium's XSalsa20-Poly1305 authenticated encryption
- Key file should have `0600` permissions (owner read/write only)
- **CRITICAL**: Backup the encryption key - data cannot be recovered without it
- In production, store key outside application directory

### Added - 2026-03-06: Incremental Database Backups

Implemented automated incremental backup system using PostgreSQL WAL (Write-Ahead Logging) archiving.

#### New Files Created

| File | Purpose |
|------|---------|
| `docker/postgres/postgresql.conf` | PostgreSQL configuration for WAL archiving |
| `docker/postgres/pg_hba.conf` | Host-based authentication for replication |
| `docker/postgres/init-backup-dirs.sh` | Initialization script for backup directories |
| `docker/backup/Dockerfile` | Backup service container |
| `docker/backup/full-backup.sh` | Daily full backup script using pg_basebackup |
| `docker/backup/incremental-backup.sh` | Hourly WAL file synchronization |
| `docker/backup/restore.sh` | Point-in-time recovery script |
| `docker/backup/scheduler.sh` | Backup scheduling daemon |
| `docs/BACKUP.md` | Comprehensive backup documentation |

#### Modified Files

| File | Changes |
|------|---------|
| `compose.yaml` | Added backup service, WAL archive volume, PostgreSQL config mounts |
| `.env` | Added backup configuration variables |
| `.github/copilot-instructions.md` | Added backup section |
| `docs/PRODUCTION_CHECKLIST.md` | Added backup security checklist |

#### Backup Features

- **Full Backups**: Daily at 2:00 AM using `pg_basebackup`
- **Incremental Backups**: Hourly WAL file synchronization
- **Point-in-Time Recovery**: Restore to any moment in time
- **Configurable Retention**: 7 days for full backups, 3 days for WAL files
- **Automated Cleanup**: Old backups automatically purged

#### New Docker Volumes

- `wal_archive`: PostgreSQL WAL files for incremental backups
- `backups`: Full backups and archived WAL files

---

## [0.1.0] - 2026-03-03

### Added

- Initial project setup based on dunglas/symfony-docker template
- User entity with email, name, surname, cristalId, roles
- Basic authentication with form login
- Home page and login page
- Role-based access control (ROLE_USER, ROLE_NURSE, ROLE_DOCTOR, ROLE_TECH_ADMIN, ROLE_MEDICAL_ADMIN, ROLE_PATIENT)
