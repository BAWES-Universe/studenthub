from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

FILES_TO_SCAN = [
    "common/config/main.php",
    "common/config/params.php",
]

SECRET_PATTERNS = {
    "Google Maps API key": re.compile(r"AIzaSy[0-9A-Za-z_-]{20,}"),
    "reCAPTCHA secret fragment": re.compile(r"6Lei9R4pAAAAAD5"),
    "Jira API token fragment": re.compile(r"eYVHMtAi16z"),
    "Algolia key fragment": re.compile(r"bce91c65c212|381f91f1c08f"),
    "IP info token fragment": re.compile(r"911bdd76f42e|fac3c2117d87"),
    "Slack webhook": re.compile(r"https://hooks\.slack\.com/services/[A-Za-z0-9_/+-]+"),
}

REQUIRED_ENV_VARS = [
    "GOOGLE_MAPS_API_KEY",
    "RECAPTCHA_SECRET_KEY",
    "JIRA_URL",
    "JIRA_EMAIL",
    "JIRA_API_TOKEN",
    "ALGOLIA_APP_ID",
    "ALGOLIA_API_KEY",
    "IPINFO_ACCESS_TOKEN",
    "SLACK_WEBHOOK_URL",
]


def main() -> int:
    failures: list[str] = []
    combined = ""

    for relative_path in FILES_TO_SCAN:
        path = ROOT / relative_path
        text = path.read_text(encoding="utf-8")
        combined += "\n" + text
        for label, pattern in SECRET_PATTERNS.items():
            if pattern.search(text):
                failures.append(f"{relative_path}: contains hardcoded {label}")

    for env_var in REQUIRED_ENV_VARS:
        if f"getenv('{env_var}')" not in combined and f'getenv("{env_var}")' not in combined:
            failures.append(f"missing env-backed config for {env_var}")

    if failures:
        for failure in failures:
            print(failure)
        return 1

    print("Third-party key hardening check passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
