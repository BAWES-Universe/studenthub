#!/usr/bin/env sh
set -eu

configs="
environments/circle-ci/common/config/main-local.php
environments/dev-server-nginx-debug/common/config/main-local.php
environments/dev-server-nginx/common/config/main-local.php
environments/dev-server-railway/common/config/main-local.php
environments/dev-server/common/config/main-local.php
environments/dev/common/config/main-local.php
environments/docker/common/config/main-local.php
environments/krushn-nginx/common/config/main-local.php
environments/krushn/common/config/main-local.php
environments/prod-nginx/common/config/main-local.php
environments/prod-railway/common/config/main-local.php
environments/prod/common/config/main-local.php
"

status=0

for config in $configs; do
  if ! git grep -qE "['\"]clientSecret['\"]\s*=>\s*getenv\('XERO_CLIENT_SECRET'\)\s*\?:\s*null" -- "$config"; then
    echo "Missing XERO_CLIENT_SECRET wiring in $config" >&2
    status=1
  fi

  if git grep -nE "['\"]clientSecret['\"]\s*=>\s*['\"][^'\"]+['\"]" -- "$config"; then
    echo "Hard-coded Xero clientSecret remains in $config" >&2
    status=1
  fi
done

if ! grep -q "XERO_CLIENT_SECRET" docs/xero-env.md; then
  echo "docs/xero-env.md must document XERO_CLIENT_SECRET" >&2
  status=1
fi

exit "$status"
