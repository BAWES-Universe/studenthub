#!/usr/bin/env bash
set -euo pipefail

controller="admin/modules/v1/controllers/XeroWebhookController.php"
legacy_key_pattern="E4OxefKZm8uPKkiz8""RkGA8t"

if rg -n "$legacy_key_pattern" "$controller" docs --glob '!tests/check-xero-webhook-signature-hardening.sh'; then
  echo "Xero webhook signing key must not be committed." >&2
  exit 1
fi

rg -n "getenv\\('XERO_WEBHOOK_SIGNING_KEY'\\)" "$controller" >/dev/null
rg -n '\$request_data = Yii::\$app->request->rawBody;' "$controller" >/dev/null
rg -n 'hash_equals\(\$generated_signature, \$provided_signature\)' "$controller" >/dev/null
rg -n "UnauthorizedHttpException" "$controller" >/dev/null

if rg -n "echo \"Signature mismatch\"|die\\(\\)|http_response_code\\(401\\)" "$controller"; then
  echo "Xero webhook signature failures should use framework 401 exceptions, not echo/die." >&2
  exit 1
fi

echo "Xero webhook signature validation is runtime-configured and fail-closed."
