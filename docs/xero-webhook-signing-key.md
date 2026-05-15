# Xero webhook signing key

The admin Xero webhook endpoint validates Xero's `x-xero-signature` header with an HMAC over the raw request body.

Set the signing key at runtime:

- `XERO_WEBHOOK_SIGNING_KEY`

Do not commit the signing key to controller code or environment templates. If this variable is missing, webhook requests fail closed with HTTP 401 and a server-side configuration log entry.
