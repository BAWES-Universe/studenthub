#!/usr/bin/env python3
"""Static guard for SMS provider credential configuration."""

from pathlib import Path
import re
import sys


ROOT = Path(__file__).resolve().parents[1]
SMS_COMPONENT = ROOT / "common" / "components" / "SMSComponent.php"
MAIN_CONFIG = ROOT / "common" / "config" / "main.php"


def fail(message):
    print(f"SMS provider hardening check failed: {message}", file=sys.stderr)
    sys.exit(1)


component = SMS_COMPONENT.read_text()
config = MAIN_CONFIG.read_text()

if re.search(r"\$apiEndpoint\s*=\s*['\"]https?://", component):
    fail("SMSComponent must not hardcode the provider endpoint")

for provider_key in ("UID", "p", "S"):
    pattern = rf"['\"]{re.escape(provider_key)}['\"]\s*=>\s*['\"]"
    if re.search(pattern, component):
        fail(f"SMSComponent must not hardcode provider parameter {provider_key}")

if "https://" not in component or "must use HTTPS" not in component:
    fail("SMSComponent must enforce HTTPS transport")

required_env = [
    "SMS_PROVIDER_ENDPOINT",
    "SMS_PROVIDER_USERNAME",
    "SMS_PROVIDER_PASSWORD",
    "SMS_PROVIDER_SENDER",
]

for env_name in required_env:
    if f"getenv('{env_name}')" not in config:
        fail(f"common config must read {env_name} from the runtime environment")

sms_block_match = re.search(
    r"'smsComponent'\s*=>\s*\[(?P<body>.*?)^\s*\],",
    config,
    re.MULTILINE | re.DOTALL,
)
if not sms_block_match:
    fail("common config must define the smsComponent block")

sms_block = sms_block_match.group("body")

for option in ("apiEndpoint", "username", "password", "sender"):
    literal_pattern = rf"['\"]{option}['\"]\s*=>\s*['\"]"
    if re.search(literal_pattern, sms_block):
        fail(f"smsComponent option {option} must not use a committed literal")

if re.search(r"['\"]apiEndpoint['\"]\s*=>\s*['\"]http://", sms_block, re.IGNORECASE):
    fail("smsComponent must not configure a plaintext HTTP endpoint")

print("SMS provider hardening check passed.")
