#!/usr/bin/env bash
set -euo pipefail

if rg -n "oneSignalCandidate(APPID|APIKey)'\\s*=>\\s*'[A-Za-z0-9-]{20,}'" environments --glob 'params-local.php'; then
  echo "OneSignal candidate credentials must come from runtime environment variables." >&2
  exit 1
fi

while IFS= read -r params_file; do
  rg -n "getenv\\('ONESIGNAL_CANDIDATE_APP_ID'\\)" "$params_file" >/dev/null || {
    echo "$params_file must read ONESIGNAL_CANDIDATE_APP_ID from the runtime environment." >&2
    exit 1
  }
  rg -n "getenv\\('ONESIGNAL_CANDIDATE_API_KEY'\\)" "$params_file" >/dev/null || {
    echo "$params_file must read ONESIGNAL_CANDIDATE_API_KEY from the runtime environment." >&2
    exit 1
  }
done < <(find environments -path '*/common/config/params-local.php' -type f | sort)

if rg -n "CURLOPT_SSL_VERIFYPEER,\\s*[Ff][Aa][Ll][Ss][Ee]" common/models/MobileNotification.php; then
  echo "OneSignal requests must not disable TLS peer verification." >&2
  exit 1
fi

rg -n "CURLOPT_CONNECTTIMEOUT" common/models/MobileNotification.php >/dev/null
rg -n "CURLOPT_TIMEOUT" common/models/MobileNotification.php >/dev/null
rg -n "CURLINFO_HTTP_CODE" common/models/MobileNotification.php >/dev/null

echo "OneSignal notification config uses runtime env vars and keeps TLS verification enabled."
