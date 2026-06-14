from pathlib import Path
import re
import sys


ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / "common" / "config" / "main.php"
MANAGER = ROOT / "common" / "components" / "CloudinaryManager.php"
DOCS = ROOT / "docs" / "setup.md"


def fail(message):
    print(f"Cloudinary hardening check failed: {message}", file=sys.stderr)
    raise SystemExit(1)


config = CONFIG.read_text(encoding="utf-8")
manager = MANAGER.read_text(encoding="utf-8")
docs = DOCS.read_text(encoding="utf-8")

for variable in [
    "CLOUDINARY_CLOUD_NAME",
    "CLOUDINARY_API_KEY",
    "CLOUDINARY_API_SECRET",
]:
    if variable not in config:
        fail(f"{variable} is not wired in common/config/main.php")
    if variable not in docs:
        fail(f"{variable} is not documented in docs/setup.md")

cloudinary_block_match = re.search(
    r"'cloudinaryManager'\s*=>\s*\[(.*?)\n\s*\],",
    config,
    flags=re.S,
)
if not cloudinary_block_match:
    fail("cloudinaryManager config block was not found")

cloudinary_block = cloudinary_block_match.group(1)

literal_patterns = [
    (r"'cloud_name'\s*=>\s*['\"][^'\"]+['\"]", "cloud_name literal"),
    (r"'api_key'\s*=>\s*['\"][^'\"]+['\"]", "api_key literal"),
    (r"'api_secret'\s*=>\s*['\"][^'\"]+['\"]", "api_secret literal"),
]
for pattern, description in literal_patterns:
    if re.search(pattern, cloudinary_block):
        fail(f"Cloudinary {description} remains in config")

required_manager_snippets = [
    "private $configured = false;",
    "normalizeConfigValue",
    "assertConfigured",
    "Cloudinary credentials are not configured.",
]
for snippet in required_manager_snippets:
    if snippet not in manager:
        fail(f"CloudinaryManager is missing {snippet!r}")

for method in ["upload", "delete", "getUrl"]:
    method_match = re.search(
        rf"public function {method}\([^)]*\)\s*\{{(.*?)\n\s*\}}",
        manager,
        flags=re.S,
    )
    if not method_match:
        fail(f"CloudinaryManager::{method} was not found")
    if "$this->assertConfigured();" not in method_match.group(1):
        fail(f"CloudinaryManager::{method} does not assert configuration")

if "new uploadApi()" in manager or "new adminApi()" in manager:
    fail("Cloudinary API classes should use their imported class names")

print("Cloudinary hardening check passed.")
