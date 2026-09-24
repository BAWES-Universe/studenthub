# Console Slack Webhook Configuration

The console app reads Slack webhook URLs from runtime environment variables instead of storing a webhook in `console/config/main.php`.

## Variables

- `CONSOLE_SLACK_WEBHOOK_URL`: Optional console-specific Slack incoming webhook URL.
- `SLACK_WEBHOOK_URL`: Shared fallback webhook URL used when `CONSOLE_SLACK_WEBHOOK_URL` is not set.

Store these values in the deployment secret manager or in local environment files that are excluded from source control. Do not commit full Slack webhook URLs.

## Regression Check

Run the console Slack hardening check before opening config changes:

```bash
python scripts/check-console-slack-hardening.py
```
