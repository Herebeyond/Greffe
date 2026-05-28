# Greffe - Kidney Transplant Platform

This repository contains the backend web platform (Symfony + FrankenPHP/Caddy)
for the BTS SIO kidney transplant follow-up project.

## Guide Utilisateur (Reference Principale)

Cette section est la reference utilisateur. Si un contenu est destine aux
utilisateurs finaux, il doit etre ajoute ici en priorite.

### Acces a la plateforme

- Portail web: https://10.187.22.27
- Page de telechargement mobile: https://std27.beaupeyrat.com/mobile/
- Lien direct APK: https://std27.beaupeyrat.com/mobile/greffe-renale.apk

### Installation de l'application mobile (Android)

1. Ouvrir https://std27.beaupeyrat.com/mobile/
2. Appuyer sur "Telecharger l'APK"
3. Ouvrir le fichier telecharge
4. Autoriser l'installation depuis cette source si Android le demande
5. Lancer l'application "Greffe Renale"

### Connexion

- L'application mobile utilise par defaut: https://std27.beaupeyrat.com
- En cas d'oubli de mot de passe, contacter un administrateur technique

### Support Utilisateur

- Verifier d'abord que le lien APK commence par https://std27.beaupeyrat.com/mobile/
- Si l'application ne demarre pas correctement, desinstaller puis reinstaller
	depuis le lien officiel ci-dessus

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

If the app is behind the school reverse proxy (`std27.beaupeyrat.com`), keep
the backend in HTTP mode to avoid redirect loops:

- `SERVER_NAME=:80`
- `CADDY_GLOBAL_OPTIONS=auto_https disable_redirects`

These are now the defaults in `compose.prod.yaml` and can be overridden if you
deploy with direct TLS on the backend.

### Mobile APK in Production (Fast Build)

Production now uses `../greffe_renale_mobile/Dockerfile.prebuilt` for the
`mobile` service, so the server does not run `flutter build apk` during
`docker compose ... --build`.

Before rebuilding on the server, update the prebuilt APK in the mobile repo:

```bash
# local/dev machine
cd ../greffe_renale_mobile
flutter build apk --release
cp build/app/outputs/flutter-apk/app-release.apk prebuilt/app-release.apk
git add prebuilt/app-release.apk
git commit -m "Update prebuilt APK"
git push
```

Then on the server, pull both repositories and rebuild from the backend repo.

## Documentation

Documentation developpeur/ops (hors guide utilisateur):

- docs index: `docs/README.md`
- backlog technique: `TODO.md`
