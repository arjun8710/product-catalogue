#!/bin/sh
set -e

# First run: create .env from the example so `docker compose up` needs no setup.
# Values passed in by docker compose take precedence over this file.
if [ ! -f .env ]; then
  cp .env.example .env
fi

# The key is not used for any persisted data (the API is stateless), so a
# per-container key generated on first start is fine.
if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force --no-interaction
fi

exec "$@"
