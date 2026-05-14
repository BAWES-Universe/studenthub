#!/bin/sh
set -eu

prod_nginx="environments/prod-nginx/common/config/main-local.php"
prod_railway="environments/prod-railway/common/config/main-local.php"

if grep -R "XW5I" "$prod_nginx" "$prod_railway" >/dev/null; then
  echo "SES SMTP key suffix XW5I must not be committed in production mailer config." >&2
  exit 1
fi

for env_name in MAIL_HOST MAIL_USERNAME MAIL_PASSWORD MAIL_PORT; do
  if ! grep -q "getenv('$env_name')" "$prod_nginx"; then
    echo "Missing getenv('$env_name') in $prod_nginx." >&2
    exit 1
  fi
done

echo "SES mailer env config check passed."
