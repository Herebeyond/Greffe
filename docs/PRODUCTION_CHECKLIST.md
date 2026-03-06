# Production Deployment Checklist

This document lists all the steps required before deploying the Kidney Transplant Management Platform from development to production.

---

## 🔐 CRITICAL: Security Configuration

### 1. Encryption Key Management

**Current Location (Development):** `var/encryption.key`

**REQUIRED ACTIONS:**

- [ ] **Move the encryption key** to a secure location OUTSIDE the application directory
  ```bash
  # Example: Move to a secure system directory
  sudo mkdir -p /etc/greffe-renale/keys
  sudo mv var/encryption.key /etc/greffe-renale/keys/encryption.key
  sudo chmod 600 /etc/greffe-renale/keys/encryption.key
  sudo chown www-data:www-data /etc/greffe-renale/keys/encryption.key
  ```

- [ ] **Set the environment variable** `ENCRYPTION_KEY_PATH` to point to the new location
  ```bash
  # In production .env.local or system environment
  ENCRYPTION_KEY_PATH=/etc/greffe-renale/keys/encryption.key
  ```

- [ ] **Backup the encryption key** to a secure offline location
  - Store in a hardware security module (HSM) if available
  - Keep encrypted backup in a vault or secure storage
  - **WARNING**: If you lose this key, ALL encrypted data will be PERMANENTLY UNREADABLE

- [ ] **Never commit the key** to version control (already in .gitignore)

---

### 2. Application Secrets

**File to edit:** `.env.local` (create on production server, never commit)

| Variable | Current Value | Production Action |
|----------|---------------|-------------------|
| `APP_ENV` | `dev` | Change to `prod` |
| `APP_SECRET` | (empty) | Generate a strong random string (32+ chars) |
| `APP_DEBUG` | `true` (implicit in dev) | Ensure `false` in prod |

**Generate APP_SECRET:**
```bash
# Using PHP
php -r "echo bin2hex(random_bytes(32));"

# Or using OpenSSL
openssl rand -hex 32
```

---

### 3. Database Configuration

**File to edit:** `.env.local` on production server

| Variable | Development Value | Production Action |
|----------|-------------------|-------------------|
| `DATABASE_URL` | `postgresql://app:!ChangeMe!@...` | Use strong password, different user |
| `POSTGRES_PASSWORD` | `!ChangeMe!` | Generate strong password |
| `POSTGRES_USER` | `app` | Consider using different username |

**Security recommendations:**
- [ ] Use a dedicated database user with minimal privileges (SELECT, INSERT, UPDATE, DELETE on app tables only)
- [ ] Enable SSL/TLS for database connections
- [ ] Restrict database access to application server IP only
- [ ] Consider using secrets management (Vault, AWS Secrets Manager, etc.)

---

### 4. Mercure Configuration (if using real-time features)

| Variable | Development Value | Production Action |
|----------|-------------------|-------------------|
| `CADDY_MERCURE_JWT_SECRET` | `!ChangeThisMercureHubJWTSecretKey!` | Generate strong secret |

---

## 📁 Files to Create/Edit on Production

### Required Files (create on server, never commit)

1. **`.env.local`** - Production environment overrides
   ```bash
   APP_ENV=prod
   APP_SECRET=<generated-secret>
   DATABASE_URL=postgresql://<user>:<password>@<host>:5432/<dbname>?serverVersion=16&charset=utf8
   ENCRYPTION_KEY_PATH=/etc/greffe-renale/keys/encryption.key
   ```

2. **`/etc/greffe-renale/keys/encryption.key`** - Moved encryption key

### Files Already Configured for Production

- `compose.prod.yaml` - Production Docker Compose configuration
- `frankenphp/conf.d/20-app.prod.ini` - Production PHP settings

---

## 🚀 Deployment Commands

### Pre-deployment

```bash
# 1. Install production dependencies only
docker compose -f compose.yaml -f compose.prod.yaml exec php composer install --no-dev --optimize-autoloader

# 2. Clear and warm up cache for production
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console cache:clear --env=prod
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console cache:warmup --env=prod

# 3. Run database migrations
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 4. Compile .env files (optional, for performance)
docker compose -f compose.yaml -f compose.prod.yaml exec php composer dump-env prod
```

### Start Production

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

---

## ✅ Security Checklist

### Application Security

- [ ] `APP_ENV=prod` is set
- [ ] `APP_DEBUG=false` (implicit when APP_ENV=prod)
- [ ] `APP_SECRET` is a strong unique value
- [ ] All default passwords changed
- [ ] HTTPS is enforced (configure in Caddyfile)
- [ ] CSRF protection enabled (already configured)
- [ ] Session cookies are secure (`cookie_secure: auto` in framework.yaml)

### Encryption Security

- [ ] Encryption key moved out of application directory
- [ ] Encryption key has restricted file permissions (0600)
- [ ] Encryption key is backed up securely
- [ ] `ENCRYPTION_KEY_PATH` environment variable is set

### Database Security

- [ ] PostgreSQL password is strong and unique
- [ ] Database user has minimal required privileges
- [ ] Database is not exposed publicly (internal network only)
- [ ] SSL/TLS enabled for database connections

### Server Security

- [ ] Firewall configured (ports 80, 443 only)
- [ ] SSH key authentication only (no password SSH)
- [ ] Regular security updates scheduled
- [ ] Log rotation configured
- [ ] Monitoring and alerting set up

### Docker Security

- [ ] Using production Docker target (`frankenphp_prod`)
- [ ] No development tools in production image
- [ ] Container runs as non-root user
- [ ] Secrets not in docker-compose.yaml (use env files)

### Backup Security

- [ ] Backup service is running (`docker compose ps backup`)
- [ ] Backups are stored on a separate volume/disk
- [ ] Backup retention is configured appropriately for compliance
- [ ] Backups are encrypted at rest (for HDS/GDPR compliance)
- [ ] Offsite backup copy is configured (S3, remote storage, etc.)
- [ ] Restore procedure is tested and documented
- [ ] Backup monitoring/alerting is configured

**Backup Configuration in `.env.local`:**
```bash
# Adjust retention for production needs
BACKUP_RETENTION_DAYS=30          # Keep full backups for 30 days
WAL_RETENTION_DAYS=7              # Keep WAL files for 7 days
INCREMENTAL_INTERVAL=1800         # Incremental backup every 30 minutes
```

---

## 📋 Compliance Considerations (Medical Data)

As this application handles sensitive medical data, ensure:

- [ ] **GDPR compliance** - Data processing agreements in place
- [ ] **HDS certification** (Hébergeur de Données de Santé) - Required in France for health data
- [ ] **Access logging** - All data access should be logged
- [ ] **Data retention policy** - Define and implement
- [ ] **Backup encryption** - Database backups must be encrypted
- [ ] **Incident response plan** - Document breach notification procedures

---

## 🔄 Regular Maintenance

### Weekly

- [ ] Review application logs for errors
- [ ] Check disk space and database size
- [ ] Verify backup integrity

### Monthly

- [ ] Apply security updates
- [ ] Review access logs for anomalies
- [ ] Test backup restoration

### Quarterly

- [ ] Rotate secrets (APP_SECRET, database passwords)
- [ ] Review user access and remove unused accounts
- [ ] Security audit of dependencies (`composer audit`)

---

## 📞 Emergency Contacts

| Role | Contact | Notes |
|------|---------|-------|
| System Administrator | | Server access |
| Database Administrator | | Database issues |
| Security Officer | | Security incidents |
| On-call Developer | | Application issues |

---

## ⚠️ Critical Reminders

1. **NEVER** commit `.env.local` or any file containing secrets
2. **NEVER** commit the encryption key (`encryption.key`)
3. **ALWAYS** test migrations on a staging environment first
4. **ALWAYS** backup the database before running migrations
5. **ALWAYS** backup the encryption key before any server changes

---

*Last updated: March 6, 2026*
