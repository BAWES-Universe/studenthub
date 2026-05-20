# Production Railway Runtime Secrets

Production Railway connection secrets must be supplied by Railway environment variables, not committed config literals.

## Required variables

Primary database:

- `DB_DSN`
- `DB_USERNAME`
- `DB_PASSWORD`

Wallet database:

- `WALLET_DB_DSN`
- `WALLET_DB_USERNAME`
- `WALLET_DB_PASSWORD`

Redis:

- `REDIS_HOSTNAME`
- `REDIS_USERNAME` when the Redis service requires a username
- `REDIS_PASSWORD`
- `REDIS_PORT` optional, defaults to `6379`
- `REDIS_DATABASE` optional, defaults to `0`

Error reporting:

- `SENTRY_DSN`

Store the real values in Railway's secret/environment UI. Keep screenshots, support evidence, and PR comments limited to variable names only.
