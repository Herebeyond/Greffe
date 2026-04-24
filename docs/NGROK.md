# Exposing the dev environment via ngrok

This guide explains how to make the kidney-transplant platform reachable from
anywhere (e.g. a Flutter mobile app running on a phone that is **not** on the
PC's hotspot) using [ngrok](https://ngrok.com/).

---

## 1. Start the backend

```powershell
docker compose up -d
```

The backend now listens on:

- `https://localhost`, `https://192.168.137.1` (HTTPS, Caddy auto-TLS)
- `:80` HTTP catch-all — **this is the port ngrok forwards to**

The override file (`compose.override.yaml`) already configures Symfony to trust
proxy headers (`SYMFONY_TRUSTED_PROXIES`, `SYMFONY_TRUSTED_HEADERS`), so the
app correctly detects HTTPS even though ngrok terminates TLS at its edge and
forwards plain HTTP to the container.

---

## 2. Start the ngrok tunnel

Install ngrok (`winget install ngrok.ngrok` or download), then:

```powershell
ngrok http 80
```

ngrok prints a public URL such as:

```
https://1234-5-6-7-8.ngrok-free.app -> http://localhost:80
```

Keep this terminal open while testing. Each restart on the free plan gives a
new subdomain — to keep a stable hostname, use a paid plan with
`ngrok http --domain=your-name.ngrok.app 80`.

> **CORS** — `/api/*` already allows any origin, so the mobile app and any web
> browser can call it via the ngrok URL without extra configuration.

---

## 3. Point the Flutter mobile app at the ngrok URL

The app reads its API base URL from secure storage at startup, so **no
rebuild is required** to switch servers.

1. Launch the app.
2. On the login screen, tap the **"Serveur : …"** button.
3. Paste the ngrok URL (e.g. `https://1234-5-6-7-8.ngrok-free.app`) and tap
   **Valider**.
4. Log in normally — all subsequent API calls use the new URL.

To revert to the compile-time default, open the dialog and tap
**Réinitialiser**.

> **ngrok free plan note** — The first request to a free ngrok URL shows an
> interstitial warning page in browsers. The Flutter app is not affected
> because it sends its own headers. If you do hit it from a browser, click
> "Visit Site" once.

---

## 4. (Optional) Skip the ngrok warning page

To bypass the browser interstitial entirely (useful for the web build of the
app), add the header `ngrok-skip-browser-warning: true` to requests, or use a
paid ngrok plan with a custom domain.

---

## 5. Stop everything

```powershell
# Ctrl+C the ngrok terminal, then:
docker compose down
```
