# Xero environment variables

Xero OAuth client secrets must be supplied by the runtime environment, not committed in environment config files.

Set `XERO_CLIENT_SECRET` anywhere the `xero` component is enabled:

- `environments/circle-ci/common/config/main-local.php`
- `environments/dev-server-nginx-debug/common/config/main-local.php`
- `environments/dev-server-nginx/common/config/main-local.php`
- `environments/dev-server-railway/common/config/main-local.php`
- `environments/dev-server/common/config/main-local.php`
- `environments/dev/common/config/main-local.php`
- `environments/docker/common/config/main-local.php`
- `environments/krushn-nginx/common/config/main-local.php`
- `environments/krushn/common/config/main-local.php`
- `environments/prod-nginx/common/config/main-local.php`
- `environments/prod-railway/common/config/main-local.php`
- `environments/prod/common/config/main-local.php`

Rotate any Xero OAuth client secrets that were previously committed before using these deployment configs.
