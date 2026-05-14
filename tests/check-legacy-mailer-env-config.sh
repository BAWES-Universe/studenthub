#!/usr/bin/env bash
set -euo pipefail

files=(
  "environments/prod/common/config/main-local.php"
  "environments/dev-server/common/config/main-local.php"
  "environments/dev-server-nginx/common/config/main-local.php"
  "environments/dev-server-nginx-debug/common/config/main-local.php"
  "environments/dev-server-railway/common/config/main-local.php"
)

blocked_patterns=(
  "SH3JXFI4"
  "VNB2AFUL"
  "TH5HBB2O"
  "TQGXUQT3"
  "smtp.elasticemail.com"
  "smtp.eu.mailgun.org"
  "smtp.sendgrid.net"
)

for file in "${files[@]}"; do
  for pattern in "${blocked_patterns[@]}"; do
    if grep -Fq "$pattern" "$file"; then
      echo "Blocked legacy mailer secret remains in $file: $pattern" >&2
      exit 1
    fi
  done

  for variable in MAIL_SCHEME MAIL_HOST MAIL_USERNAME MAIL_PASSWORD MAIL_PORT MAIL_ENCRYPTION; do
    if ! grep -Fq "$variable" "$file"; then
      echo "$file does not read $variable from the environment" >&2
      exit 1
    fi
  done
done

echo "Legacy mailer configs use MAIL_* env vars and contain no blocked SMTP secrets."
