#!/usr/bin/env bash
set -euo pipefail

TEMP_CONFIG="common/config/main.php"
PERMANENT_CONFIG="environments/prod-railway/common/config/main-local.php"

# Assert that a config file still contains an expected env-backed setting.
# This keeps the security regression check readable as new S3 keys are added.
require_line() {
  local file="$1"
  local expected="$2"

  if ! grep -Fq "$expected" "$file"; then
    echo "Missing expected config in $file: $expected" >&2
    exit 1
  fi
}

require_line "$TEMP_CONFIG" "getenv('AWS_TEMP_BUCKET_KEY') ?: ''"
require_line "$TEMP_CONFIG" "getenv('AWS_TEMP_BUCKET_SECRET') ?: ''"
require_line "$TEMP_CONFIG" "getenv('AWS_TEMP_BUCKET_REGION') ?: 'eu-west-2'"
require_line "$TEMP_CONFIG" "getenv('AWS_TEMP_BUCKET_NAME') ?: 'studenthub-public-anyone-can-upload-24hr-expiry'"

require_line "$PERMANENT_CONFIG" "getenv('AWS_PERMANENT_S3_ACCESS_KEY_ID') ?: ''"
require_line "$PERMANENT_CONFIG" "getenv('AWS_PERMANENT_S3_SECRET_ACCESS_KEY') ?: ''"
require_line "$PERMANENT_CONFIG" "getenv('AWS_PERMANENT_S3_REGION') ?: 'eu-west-2'"
require_line "$PERMANENT_CONFIG" "getenv('AWS_PERMANENT_S3_BUCKET') ?: 'studenthub-uploads'"

temp_block="$(sed -n "/'temporaryBucketResourceManager' => \\[/,/^[[:space:]]*\\],/p" "$TEMP_CONFIG")"
permanent_block="$(sed -n "/'resourceManager' => \\[/,/^[[:space:]]*\\],/p" "$PERMANENT_CONFIG")"

if printf "%s\n%s\n" "$temp_block" "$permanent_block" | grep -Eq '(AKIA|ASIA)[A-Z0-9]{16}|ODY2X|WCUM'; then
  echo "S3 upload credential values must stay out of the temp/permanent bucket config blocks." >&2
  exit 1
fi

echo "S3 upload config is env-backed."
