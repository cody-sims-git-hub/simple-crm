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

# 2b. Give .env its two readers: the container (uid 33) and docker compose
#     (you). cp above creates it owned by YOU alone, which the container cannot
#     read — see "The .env ownership trap" under Notes. Deploys run this
#     automatically; it is listed here so a first-time bootstrap is correct too.
bash deploy/env-guard.sh fix

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

### The .env ownership trap

`.env` is bind-mounted read-only into the container, and the image runs as
**www-data (uid 33)** — not as the user who deployed it. `cp .env.production.example .env`
creates the file owned by the deploying user at mode 600, which uid 33 cannot read.

When that happens the failure is quiet and durable:

- `APP_KEY` resolves to an empty string
- every request that touches the encrypter throws `MissingAppKeyException`
- `AUTORUN_LARAVEL_CONFIG_CACHE` caches that empty config at boot, so it survives restarts
- `SQLite` falls back to the framework default path instead of the one in `.env`

Production ran that way from 2026-07-22 to 2026-08-20 — every page 500ing, while
Docker reported the container `healthy`, because the healthcheck hit `/up`, which
renders without resolving the encrypter.

Two things prevent a repeat, both in `deploy/env-guard.sh`:

| | |
|---|---|
| `env-guard.sh fix` | sets `.env` to `33:<deploy group>` mode `640`. Runs before `docker compose up` on every deploy, and is a bootstrap step above. Self-healing — see below. |
| `env-guard.sh verify` | asserts **both** readers work — the deploying user and the running container — that `app.key` is non-empty, and that `GET /login` renders 200 from inside the container. Exits non-zero, failing the deploy, rather than letting it boot into a cached empty config. |

The real-page check runs **inside the container** deliberately, so no CDN sits in
the path of the assertion that matters. Reachability through the public URL is
checked separately by the workflow — but *from the VPS*, not from the runner:

| probed from | `/up` | `/login` |
|---|---|---|
| a residential IP | 200 | 200 |
| the VPS itself | 200 | 200 |
| a GitHub Actions runner | **403** | **403** |

Cloudflare returns 403 to the runner's address range on every path, and this used
to pass (last green run 2026-06-22), so it is an edge-config change rather than
anything in this repo. A probe from CI therefore measures Cloudflare's opinion of
GitHub's IPs and says nothing about the deploy. Running it from the VPS keeps the
whole public path under test — same DNS, same edge, same Caddy, same container —
with the only difference being an origin Cloudflare will answer.

Between the two checks both halves are covered: a broken app behind a working
edge, and a working app behind a broken edge.

**`fix` repairs ownership itself rather than telling you to.** `chown` to another
uid is root-only and the deploy deliberately runs as an ordinary user, so it
escalates through three tiers, cheapest first:

1. **direct** — already root (hand bootstrap, or a root-run deploy)
2. **`sudo -n`** — where sudoers allows it. Not assumed: *this box requires a
   password*, so on this host the tier is a no-op
3. **a throwaway root container** — `docker run --rm --user 0:0` with the
   checkout bind-mounted

Tier 3 is what makes a rebuilt VPS come up correct on its own, and it grants
nothing new: docker group membership is already root-equivalent, and the deploy
user must have it or `docker compose up` could never run. There is no host where
the deploy works but this tier doesn't.

If all three fail, the deploy stops with the exact `sudo` command to run. It
stops *before* `docker compose build`, so a broken `.env` aborts the deploy
rather than recreating the container into a bad config — the running site is
left untouched.

Verified against the real failure on 2026-08-20: `.env` was reset to
`cody:cody 600`, the deploy was run, and it self-healed to `www-data:cody 640`
and went green without intervention.

**`.env` has two readers, and they are different users.** The container reads it as
uid 33; `docker compose` reads it as whoever deploys, because compose treats a
project-root `.env` as its own variable-interpolation file. Giving it exclusively
to uid 33 at mode `600` satisfies the first and breaks the second with
`open /path/.env: permission denied` on every compose command — which is exactly
what the first attempt at this fix did. Hence `640`: owned by the app user,
grouped to the deploying user, still not world-readable.

`PUID`/`PGID` look like the natural fix and do not work on this image — it starts
as www-data, so the base image's remap script has no privileges and skips
silently. The container healthcheck and the deploy's health verify both hit
`/login` rather than `/up` for the same reason the outage was invisible.

### Other notes

- **Demo login:** `demo@example.com` / `password`. New registrations get their own
  isolated starter pipeline.
- **Data** lives only in the `crm-data` volume. `docker compose down` keeps it;
  `docker volume rm <stack>_crm-data` wipes it.
- **Updates:** `git pull && docker compose up -d --build`.
- The app trusts the proxy's forwarded headers (`bootstrap/app.php`), so it
  generates correct `https://` URLs behind Caddy.
