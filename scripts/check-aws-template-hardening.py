#!/usr/bin/env python3
"""Regression checks for legacy AWS deployment template hardening."""

from pathlib import Path
import re
import sys


ROOT = Path(__file__).resolve().parents[1]
TEMPLATE_FILES = [
    ROOT / "aws-template.sh",
    ROOT / "aws-template-dev.sh",
    ROOT / "aws-template-docker.sh",
]

FORBIDDEN_PATTERNS = {
    r'echo\s+"github private key"\s*>': "inline private-key placeholder write",
    r'echo\s+"github public key"\s*>': "inline public-key placeholder write",
    r"\b\d{12}\.dkr\.ecr\.[a-z0-9-]+\.amazonaws\.com\b": "hard-coded ECR registry",
}

REQUIRED_PATTERNS = {
    "GITHUB_DEPLOY_KEY_PATH": "deploy key path is required from the environment",
    "install -m 600": "private deploy key is installed with restrictive permissions",
}


def main() -> int:
    failures = []

    for template in TEMPLATE_FILES:
        text = template.read_text()

        for pattern, description in FORBIDDEN_PATTERNS.items():
            if re.search(pattern, text):
                failures.append(f"{template.name}: found {description}")

        for pattern, description in REQUIRED_PATTERNS.items():
            if pattern not in text:
                failures.append(f"{template.name}: missing {description}")

    docker_template = (ROOT / "aws-template-docker.sh").read_text()
    for required in ("AWS_ECR_ACCOUNT_ID", "AWS_ECR_REGION", "AWS_ECR_REGISTRY", "AWS_ECR_IMAGE"):
        if required not in docker_template:
            failures.append(f"aws-template-docker.sh: missing {required} ECR variable")

    if failures:
        for failure in failures:
            print(failure, file=sys.stderr)
        return 1

    print("AWS deployment templates use env-driven deploy keys and ECR registry references.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
