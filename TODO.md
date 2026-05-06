# TODO

## Production Switch Checklist

- [ ] Set `APP_ENV=prod` in `.env.local` (or environment variables).
- [ ] Keep `SERVER_NAME` set to the final hostname/IP used by users.
- [ ] Replace default secrets with strong values:
  - `APP_SECRET`
  - `POSTGRES_PASSWORD`
  - `CADDY_MERCURE_JWT_SECRET`
- [ ] Verify TLS certificate files exist and are valid:
  - `frankenphp/certs/tls.pem`
  - `frankenphp/certs/tls.key`
- [ ] Ensure encryption key is persisted and backed up:
  - `/app/var/encryption/encryption.key`
- [ ] Build and start with production compose files:
  - `docker compose -f compose.yaml -f compose.prod.yaml up -d --build`
- [ ] Confirm mobile is reachable through backend route:
  - `https://<server>/mobile/`
- [ ] Remove or restrict direct access to admin tools in prod:
  - `adminer` on port `8080`
- [ ] Validate trusted proxies/headers when behind reverse proxies.
- [ ] Run smoke tests for login, patient access, break-the-glass, and backup jobs.
- [ ] Confirm backup job logs and retention behavior after 24h.

## Security and Operations

- [ ] Request a DNS name from school IT (recommended) for cleaner user access.
- [ ] If DNS is provided, update `SERVER_NAME` and certificate strategy.
- [ ] Configure firewall rules to only expose required ports.
- [ ] Restrict database/admin endpoints to admin network only.
- [ ] Add periodic restore drill (monthly) from `docs/BACKUP.md`.
- [ ] Set up monitoring/alerts for:
  - Container health
  - Backup freshness
  - Disk space

## Mobile Distribution

- [ ] Keep APK versioning strategy (file name or release metadata).
- [ ] Add checksum (SHA-256) for APK integrity verification.
- [ ] Add release notes section on APK download page.
- [ ] Plan migration to `/mobile/` URL only (no `:3000`) for production-facing use.

## Documentation Hygiene

- [ ] Keep `README.md` deployment URLs in sync with real server config.
- [ ] Update `docs/CHANGELOG.md` on major infra and security changes.
- [ ] Add a short runbook for incident recovery (service down, cert expiry, DB restore).
