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
APP_GID="${APP_GID:-33}"
SERVICE="${SERVICE:-crm-app}"
ENV_FILE="${ENV_FILE:-.env}"
ENV_IN_CONTAINER="/var/www/html/.env"

die() { echo "env-guard ERROR: $*" >&2; exit 1; }

case "${1:-}" in
  fix)
    [ -f "$ENV_FILE" ] || die "$ENV_FILE does not exist — see DEPLOYMENT.md. The app cannot boot without it."

    before="$(stat -c '%u:%g' "$ENV_FILE")"

    # Steady state is a no-op: ownership persists across deploys because .env is
    # never recreated. Only a fresh bootstrap or a hand-edited/replaced file
    # lands here needing work — which matters, because the deploy runs as an
    # ordinary user and only root can chown to another uid.
    if [ "$before" = "$APP_UID:$APP_GID" ]; then
      chmod 600 "$ENV_FILE" 2>/dev/null || true
      echo "env-guard: $ENV_FILE already owned by $APP_UID:$APP_GID — nothing to do"
      exit 0
    fi

    echo "env-guard: $ENV_FILE is owned by $before, needs $APP_UID:$APP_GID — fixing"
    # Mode stays 600 throughout. The file holds credentials; the fix is to give
    # it to the right user, not to widen it.
    if ! chown "$APP_UID:$APP_GID" "$ENV_FILE" 2>/dev/null; then
      sudo -n chown "$APP_UID:$APP_GID" "$ENV_FILE" 2>/dev/null || true
    fi
    chmod 600 "$ENV_FILE" 2>/dev/null || true

    after="$(stat -c '%u:%g' "$ENV_FILE")"
    [ "$after" = "$APP_UID:$APP_GID" ] || die "could not change ownership of $ENV_FILE.
       It is $after and must be $APP_UID:$APP_GID for the container to read it.
       This deploy runs as $(id -un) (uid $(id -u)), and only root can chown to another user.
       Run this on the host, then re-run the deploy:
           sudo chown $APP_UID:$APP_GID '$PWD/$ENV_FILE' && sudo chmod 600 '$PWD/$ENV_FILE'"

    echo "env-guard: $ENV_FILE $before -> $after (uid $APP_UID is the container's runtime user)"
    ;;

  verify)
    docker compose ps --status running --services 2>/dev/null | grep -qx "$SERVICE" \
      || die "service '$SERVICE' is not running"

    if ! docker compose exec -T "$SERVICE" sh -lc "[ -r '$ENV_IN_CONTAINER' ]"; then
      runtime_uid="$(docker compose exec -T "$SERVICE" sh -lc 'id -u' 2>/dev/null | tr -d '\r')"
      die "the container cannot read $ENV_IN_CONTAINER.
       It runs as uid ${runtime_uid:-unknown}; the file is owned by uid $(stat -c %u "$ENV_FILE").
       Fix: deploy/env-guard.sh fix && docker compose up -d --force-recreate"
    fi

    key_len="$(docker compose exec -T "$SERVICE" \
      php -r 'echo strlen(config("app.key") ?? "");' 2>/dev/null | tr -d '\r')"

    case "$key_len" in
      ''|*[!0-9]*) die "could not read app.key length from the container (got: '$key_len')" ;;
    esac

    [ "$key_len" -gt 0 ] || die "APP_KEY resolves empty inside the container.
       The config cache was almost certainly built while .env was unreadable.
       Fix: deploy/env-guard.sh fix && docker compose up -d --force-recreate"

    echo "env-guard: OK — .env readable, app.key resolves ($key_len chars)"
    ;;

  *)
    die "usage: $0 {fix|verify}"
    ;;
esac
