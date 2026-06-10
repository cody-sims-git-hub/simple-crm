# Deployment

SimpleCRM runs as a single Docker container behind a [Caddy](https://caddyserver.com)
reverse proxy that terminates TLS (automatic Let's Encrypt). The app image
(`serversideup/php:8.4-fpm-nginx`) serves `public/` on port **8080** and uses a
**SQLite** database persisted on a named volume.

## Prerequisites

- A host running Docker + Docker Compose.
- A Caddy container on an external Docker network named `proxy`.
- A DNS **A** record for your subdomain pointing at the host's public IP.

## Steps

```bash
# 1. Clone
git clone https://github.com/cody-sims-git-hub/simple-crm.git
cd simple-crm

# 2. Create the production env file and generate an app key
cp .env.production.example .env
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
#   (adjust APP_URL in .env if using a different domain)

# 3. Build & start (migrations run automatically on boot)
docker compose up -d --build

# 4. Seed the demo account + starter pipeline (idempotent)
docker compose exec -u www-data crm-app php artisan db:seed --force
```

Then add a site block to the Caddyfile and reload:

```caddy
demo.simsdigitalpartners.com {
    reverse_proxy crm-app:8080
}
```

```bash
docker exec caddy caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
```

## Notes

- **Demo login:** `demo@example.com` / `password`. New registrations get their own
  isolated starter pipeline.
- **Data** lives only in the `crm-data` volume. `docker compose down` keeps it;
  `docker volume rm <stack>_crm-data` wipes it.
- **Updates:** `git pull && docker compose up -d --build`.
- The app trusts the proxy's forwarded headers (`bootstrap/app.php`), so it
  generates correct `https://` URLs behind Caddy.
