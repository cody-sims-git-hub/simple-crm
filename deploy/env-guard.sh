#!/usr/bin/env bash
#
# Guard the one thing that took production down for 30 days.
#
# `.env` is bind-mounted read-only into the container. The image runs as
# www-data (uid 33), but DEPLOYMENT.md creates `.env` as the deploying user
# (uid 1000) with mode 600 — so the app could not read its own config. APP_KEY
# resolved to an empty string, every request touching the encrypter threw
# MissingAppKeyException, and because AUTORUN_LARAVEL_CONFIG_CACHE runs at boot,
# that empty config was cached and survived restarts.
#
# It went unnoticed from 2026-07-22 to 2026-08-20 because the healthcheck hit
# Laravel's /up route, which renders without resolving the encrypter and
# answered 200 the whole time.
#
# Two jobs here, and the second is the one that matters:
#
#   fix     — make the ownership correct, so a fresh clone or a rebuilt VPS
#             comes up right without anyone remembering this file exists.
#   verify  — prove the running container can actually read .env and resolve a
#             real APP_KEY, and fail the deploy loudly if it cannot.
#
# `fix` is the remedy; `verify` is the defence. A deploy that silently boots
# into a cached empty config is the exact failure this exists to prevent, so
# `verify` exits non-zero rather than warning.
#
# Usage:  deploy/env-guard.sh fix
#         deploy/env-guard.sh verify
#
# APP_UID/APP_GID can be overridden if the base image's runtime user ever
# changes; `verify` catches it empirically either way.

set -euo pipefail

APP_UID="${APP_UID:-33}"   # www-data in the serversideup/php base image
SERVICE="${SERVICE:-crm-app}"
WAIT_SECONDS="${WAIT_SECONDS:-120}"   # ceiling for the post-deploy readiness wait
ENV_FILE="${ENV_FILE:-.env}"
ENV_IN_CONTAINER="/var/www/html/.env"

# .env has TWO readers and they are different users:
#
#   1. the container, as uid 33 (www-data), reading Laravel's config
#   2. `docker compose` itself, as whoever runs the deploy, because compose
#      treats a project-root .env as its own variable-interpolation file
#
# Giving it exclusively to uid 33 at mode 600 satisfies (1) and breaks (2) with
# "open /path/.env: permission denied" — which is precisely what happened when
# the first fix went in. So: owned by the app user, group is the deploying user,
# mode 640. Both readers, nobody else, still not world-readable.
#
# The group is derived from whoever owns the checkout rather than hardcoded, so
# this is correct on any box without editing the script.
DEPLOY_GID="${DEPLOY_GID:-$(stat -c %g .)}"

die() { echo "env-guard ERROR: $*" >&2; exit 1; }

case "${1:-}" in
  fix)
    [ -f "$ENV_FILE" ] || die "$ENV_FILE does not exist — see DEPLOYMENT.md. The app cannot boot without it."

    want="$APP_UID:$DEPLOY_GID"
    before="$(stat -c '%u:%g %a' "$ENV_FILE")"

    # Steady state is a no-op: ownership persists across deploys because .env is
    # never recreated. Only a fresh bootstrap or a replaced file lands here
    # needing work — which matters, because the deploy runs as an ordinary user
    # and only root can chown to another uid.
    if [ "$before" = "$want 640" ]; then
      echo "env-guard: $ENV_FILE already $want mode 640 — nothing to do"
      exit 0
    fi

    echo "env-guard: $ENV_FILE is $before, wants $want mode 640 — fixing"
    if ! { chown "$APP_UID:$DEPLOY_GID" "$ENV_FILE" && chmod 640 "$ENV_FILE"; } 2>/dev/null; then
      sudo -n chown "$APP_UID:$DEPLOY_GID" "$ENV_FILE" 2>/dev/null || true
      sudo -n chmod 640 "$ENV_FILE" 2>/dev/null || true
    fi

    after="$(stat -c '%u:%g %a' "$ENV_FILE")"
    [ "$after" = "$want 640" ] || die "could not set ownership on $ENV_FILE.
       It is '$after' and must be '$want 640' — uid $APP_UID is the container's
       runtime user, gid $DEPLOY_GID is the group that owns this checkout so
       docker compose can still read it.
       This deploy runs as $(id -un) (uid $(id -u)); only root can chown to another user.
       Run this on the host, then re-run the deploy:
           sudo chown $APP_UID:$DEPLOY_GID '$PWD/$ENV_FILE' && sudo chmod 640 '$PWD/$ENV_FILE'"

    echo "env-guard: $ENV_FILE $before -> $after"
    ;;

  verify)
    # Reader 2 first — if this fails, no docker compose command below would have
    # worked either. This is the check that was missing when the first fix gave
    # .env exclusively to uid 33 and broke every subsequent deploy.
    [ -r "$ENV_FILE" ] || die "the deploying user ($(id -un), uid $(id -u)) cannot read $ENV_FILE.
       docker compose reads a project-root .env for variable interpolation, so
       every compose command will fail with 'permission denied'.
       Fix: deploy/env-guard.sh fix"

    docker compose ps --status running --services 2>/dev/null | grep -qx "$SERVICE" \
      || die "service '$SERVICE' is not running"

    # `docker compose up -d` returns as soon as the container is started, not
    # when it is ready. The boot automations (config:cache, migrate) run inside
    # the container after that, so asserting immediately races them and reports
    # a missing config cache that is simply not written yet.
    #
    # Wait on the container healthcheck, which is the thing that already knows.
    # Bounded, because a container that never becomes healthy is a real failure
    # and this must not hang a deploy.
    printf 'env-guard: waiting for %s to become healthy' "$SERVICE"
    health=""
    for _ in $(seq 1 "$WAIT_SECONDS"); do
      health="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' \
        "$(docker compose ps -q "$SERVICE")" 2>/dev/null || echo unknown)"
      case "$health" in
        healthy|none) break ;;
      esac
      printf '.'
      sleep 1
    done
    printf '\n'

    [ "$health" = "healthy" ] || [ "$health" = "none" ] || die "$SERVICE did not become healthy within ${WAIT_SECONDS}s (last state: ${health:-unknown}).
       Check: docker compose logs --tail 50 $SERVICE"

    if ! docker compose exec -T "$SERVICE" sh -lc "[ -r '$ENV_IN_CONTAINER' ]"; then
      runtime_uid="$(docker compose exec -T "$SERVICE" sh -lc 'id -u' 2>/dev/null | tr -d '\r')"
      die "the container cannot read $ENV_IN_CONTAINER.
       It runs as uid ${runtime_uid:-unknown}; the file is owned by uid $(stat -c %u "$ENV_FILE").
       Fix: deploy/env-guard.sh fix && docker compose up -d --force-recreate"
    fi

    # Read the key out of the CACHED config, which is what the running app
    # actually booted with — the whole failure mode was a cache built while
    # .env was unreadable, so that file is the honest thing to inspect.
    #
    # Note it is `include`, not config(): a bare `php -r` has no framework
    # bootstrapped, so config() is undefined there and the command dies with an
    # empty result. And `|| true` matters — under `set -e` a failing command
    # substitution aborts the script before any of the checks below get to run,
    # which produces a bare exit 255 with no explanation.
    key_len="$(docker compose exec -T "$SERVICE" php -r '
      $f = "bootstrap/cache/config.php";
      $c = is_file($f) ? include $f : null;
      echo is_array($c) ? strlen($c["app"]["key"] ?? "") : "nocache";
    ' 2>/dev/null | tr -d '\r' || true)"

    [ "$key_len" != "nocache" ] || die "no cached config in the container.
       AUTORUN_LARAVEL_CONFIG_CACHE should have written bootstrap/cache/config.php at boot.
       Check: docker compose logs $SERVICE"

    case "$key_len" in
      ''|*[!0-9]*) die "could not read app.key from the container (got: '$key_len').
       Check: docker compose exec $SERVICE php -r 'var_dump(is_file(\"bootstrap/cache/config.php\"));'" ;;
    esac

    [ "$key_len" -gt 0 ] || die "APP_KEY resolves empty inside the container.
       The config cache was almost certainly built while .env was unreadable.
       Fix: deploy/env-guard.sh fix && docker compose up -d --force-recreate"

    echo "env-guard: OK — .env readable by both the deploying user and the container; app.key resolves ($key_len chars)"
    ;;

  *)
    die "usage: $0 {fix|verify}"
    ;;
esac
