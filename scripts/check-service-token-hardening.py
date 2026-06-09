from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]

SECRET_PATTERNS = [
    ("raw microservice bearer token", re.compile(r"Bearer\s+[A-Za-z0-9_-]{24,}")),
    ("browser-bundled Xero bearer token", re.compile(r"Bearer\s+eyJhbGciOiJSUzI1NiIsImtpZCI6")),
]

REQUIRED_ENV_REFERENCES = [
    ("WALLET_API_KEY", ROOT / "environments"),
    ("YEASTER_MICROSERVICE_API_KEY", ROOT / "environments"),
    ("EVENT_MANAGER_ENDPOINT_API_KEY", ROOT / "environments"),
]

SEARCH_SUFFIXES = {".php", ".js", ".md", ".yml", ".yaml", ".json"}
SKIP_DIRS = {".git", "vendor"}


def iter_files():
    """Yield repository files that can contain checked-in service tokens."""
    for path in ROOT.rglob("*"):
        if not path.is_file() or path.suffix not in SEARCH_SUFFIXES:
            continue
        if any(part in SKIP_DIRS for part in path.parts):
            continue
        yield path


def main():
    """Validate service tokens are referenced from runtime environment sources."""
    failures = []
    for path in iter_files():
        text = path.read_text(encoding="utf-8", errors="ignore")
        rel = path.relative_to(ROOT)
        for label, pattern in SECRET_PATTERNS:
            if pattern.search(text):
                failures.append(f"{rel}: contains {label}")

    wallet_literal = re.compile(
        r"['\"]walletManager['\"]\s*=>\s*\[[\s\S]*?['\"]apiKey['\"]\s*=>\s*['\"](?!\s*\))",
        re.MULTILINE,
    )
    for path in (ROOT / "environments").rglob("main-local.php"):
        text = path.read_text(encoding="utf-8", errors="ignore")
        if wallet_literal.search(text):
            failures.append(f"{path.relative_to(ROOT)}: walletManager apiKey is not env-backed")

    for env_name, base in REQUIRED_ENV_REFERENCES:
        found = False
        env_pattern = re.escape(env_name)
        access_pattern = re.compile(
            rf"(?:getenv|env)\s*\(\s*['\"]{env_pattern}['\"]\s*\)"
            rf"|\$_(?:ENV|SERVER)\s*\[\s*['\"]{env_pattern}['\"]\s*\]"
        )
        for path in base.rglob("*.php"):
            text = path.read_text(encoding="utf-8", errors="ignore")
            if access_pattern.search(text):
                found = True
                break
        if not found:
            failures.append(f"missing environment reference: {env_name}")

    if failures:
        print("Service token hardening check failed:")
        for failure in failures:
            print(f"- {failure}")
        return 1

    print("Service token hardening check passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
