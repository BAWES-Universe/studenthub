#!/usr/bin/env python3
"""Validate that console Slack webhook config stays environment-backed."""

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONSOLE_CONFIG = ROOT / "console" / "config" / "main.php"


def main() -> int:
    """Return a non-zero exit code when console Slack hardening regresses."""

    text = CONSOLE_CONFIG.read_text(encoding="utf-8")
    failures = []

    if "hooks.slack.com/services" in text:
        failures.append("console/config/main.php still contains a Slack webhook URL")

    if "CONSOLE_SLACK_WEBHOOK_URL" not in text:
        failures.append("console/config/main.php does not read CONSOLE_SLACK_WEBHOOK_URL")

    if "SLACK_WEBHOOK_URL" not in text:
        failures.append("console/config/main.php does not include the shared SLACK_WEBHOOK_URL fallback")

    if "'url' => $consoleSlackWebhookUrl" not in text:
        failures.append("console/config/main.php does not wire the Slack client URL through the env-backed value")

    if failures:
        print("Console Slack webhook hardening check failed:")
        for failure in failures:
            print(f"- {failure}")
        return 1

    print("Console Slack webhook hardening check passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
