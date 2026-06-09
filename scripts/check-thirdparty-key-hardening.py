#!/usr/bin/env python3
"""Fail if third-party integration credentials are hardcoded in config."""

from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

CHECKS = [
    (
        ROOT / "common/config/main.php",
        [
            re.compile(r"6L[0-9A-Za-z_-]{30,}"),
            re.compile(r"AIza[0-9A-Za-z_-]{20,}"),
            re.compile(r"https://hooks\.slack\.com/services/[^'\"\s]+"),
            re.compile(r"'apiToken'\s*=>\s*['\"][^'\"]+['\"]"),
            re.compile(r"'apiKey'\s*=>\s*['\"][a-f0-9]{24,}['\"]", re.I),
            re.compile(r"'accessKey'\s*=>\s*['\"][a-f0-9]{12,}['\"]", re.I),
        ],
    ),
    (
        ROOT / "common/config/params.php",
        [
            re.compile(r"AIza[0-9A-Za-z_-]{20,}"),
            re.compile(r"'google_api_key'\s*=>\s*['\"][^'\"]+['\"]"),
        ],
    ),
]


def main() -> int:
    failures: list[str] = []
    for path, patterns in CHECKS:
        text = path.read_text()
        for pattern in patterns:
            for match in pattern.finditer(text):
                line = text.count("\n", 0, match.start()) + 1
                failures.append(f"{path.relative_to(ROOT)}:{line}: {pattern.pattern}")

    if failures:
        print("Third-party key hardening check failed:")
        for failure in failures:
            print(f"  - {failure}")
        return 1

    print("Third-party key hardening check passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
