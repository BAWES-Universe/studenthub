#!/usr/bin/env python3
"""Fail when remediated AWS key material is reintroduced to patched configs."""

from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]

CHECKS = {
    "common/config/main.php": {
        "component": "temporaryBucketResourceManager",
        "blocked_suffixes": ["VN5ODY2X"],
    },
    "environments/prod-railway/common/config/main-local.php": {
        "component": "resourceManager",
        "blocked_suffixes": ["WZZEWCUM"],
    },
}

REQUIRED_ENV_REFERENCES = {
    "common/config/main.php": [
        "AWS_TEMP_BUCKET_KEY",
        "AWS_TEMP_BUCKET_SECRET",
        "AWS_TEMP_BUCKET_NAME",
    ],
    "environments/prod-railway/common/config/main-local.php": [
        "AWS_PERMANENT_S3_ACCESS_KEY_ID",
        "AWS_PERMANENT_S3_SECRET_ACCESS_KEY",
        "AWS_PERMANENT_S3_REGION",
        "AWS_PERMANENT_S3_BUCKET",
    ],
}

AWS_ACCESS_KEY_PATTERN = re.compile(r"AKIA[0-9A-Z]{16}")
PHP_BLOCK_COMMENT_PATTERN = re.compile(r"/\*.*?\*/", re.DOTALL)


def strip_php_comments(text: str) -> str:
    text = PHP_BLOCK_COMMENT_PATTERN.sub("", text)
    return "\n".join(line.split("//", 1)[0] for line in text.splitlines())


def extract_component(text: str, component_name: str) -> str:
    marker = f"'{component_name}' => ["
    start = text.find(marker)
    if start == -1:
        return ""

    depth = 0
    for index in range(start, len(text)):
        char = text[index]
        if char == "[":
            depth += 1
        elif char == "]":
            depth -= 1
            if depth == 0:
                return text[start : index + 1]

    return text[start:]


def main() -> int:
    failures = []

    for relative_path, config in CHECKS.items():
        path = ROOT / relative_path
        text = path.read_text()
        component_text = extract_component(text, config["component"])
        if not component_text:
            failures.append(f"{relative_path}: missing component {config['component']}")
            continue

        for suffix in config["blocked_suffixes"]:
            if suffix in component_text:
                failures.append(f"{relative_path}: blocked AWS key suffix {suffix} is still present")

        uncommented = "\n".join(
            line for line in component_text.splitlines() if not line.lstrip().startswith("//")
        )
        for match in AWS_ACCESS_KEY_PATTERN.findall(uncommented):
            failures.append(f"{relative_path}: uncommented AWS access key {match} is present")

    for relative_path, env_names in REQUIRED_ENV_REFERENCES.items():
        text = strip_php_comments((ROOT / relative_path).read_text())
        for env_name in env_names:
            pattern = re.compile(rf"getenv\(\s*['\"]{re.escape(env_name)}['\"]\s*\)")
            if not pattern.search(text):
                failures.append(f"{relative_path}: missing env var reference {env_name}")

    if failures:
        print("Sensitive AWS config check failed:")
        for failure in failures:
            print(f"- {failure}")
        return 1

    print("Sensitive AWS config check passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
