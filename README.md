# Greffe - Kidney Transplant Platform

This repository contains the backend web platform (Symfony + FrankenPHP/Caddy)
for the BTS SIO kidney transplant follow-up project.

## Current Deployment Status

The project is deployed on the school server with:

- `SERVER_NAME=10.187.22.27`
- TLS certificates mounted from `frankenphp/certs` (`tls.pem`, `tls.key`)
- Public container ports on the host: `80`, `443`, `443/udp`, `8080`

Current access URLs from the school network:

- Web app: `https://10.187.22.27`
- Mobile APK page: `https://std27.beaupeyrat.com/mobile/`
- APK direct link: `https://std27.beaupeyrat.com/mobile/greffe-renale.apk`
- Adminer: `http://10.187.22.27:8080`

Note: `APP_ENV` is currently set to `dev` on the server (`.env.local`).

## Services

- `php`: Symfony app served by FrankenPHP + Caddy
- `database`: PostgreSQL 16
- `backup`: automated full + incremental backup service
- `adminer`: database administration UI
- `mobile`: static APK distribution container

## Compose Files

- `compose.yaml`: base stack
- `compose.override.yaml`: development overrides (bind mounts, debug)
- `compose.prod.yaml`: production overrides (frankenphp_prod, mobile service, `/mobile/` reverse proxy, mounted certs)

## Run Commands

Development stack:

```bash
docker compose up -d
docker compose ps
```

Production stack:

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
docker compose -f compose.yaml -f compose.prod.yaml ps
```

## Documentation

- `docs/BACKUP.md`: backup architecture and restore procedures
- `docs/NGROK.md`: remote dev access through ngrok
- `docs/PATIENT_ACCESS_LEGAL.md`: legal basis and patient access control model
- `docs/CHANGELOG.md`: notable project changes
- `TODO.md`: backlog for changes, upgrades, and production hardening
