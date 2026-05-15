#!/usr/bin/env bash
set -euo pipefail

if rg -n "oneSignalCandidate(APPID|APIKey)'\\s*=>\\s*'[A-Za-z0-9-]{20,}'" environments --glob 'params-local.php'; then
  echo "OneSignal candidate credentials must come from runtime environment variables." >&2
  exit 1
fi

rg -n "getenv\\('ONESIGNAL_CANDIDATE_APP_ID'\\)" environments --glob 'params-local.php' >/dev/null
rg -n "getenv\\('ONESIGNAL_CANDIDATE_API_KEY'\\)" environments --glob 'params-local.php' >/dev/null

if rg -n "CURLOPT_SSL_VERIFYPEER,\\s*FALSE" common/models/MobileNotification.php; then
  echo "OneSignal requests must not disable TLS peer verification." >&2
  exit 1
fi

echo "OneSignal notification config uses runtime env vars and keeps TLS verification enabled."
