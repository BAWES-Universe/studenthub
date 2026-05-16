import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / "environments/prod-railway/common/config/main-local.php"
DOCS = ROOT / "docs/prod-railway-runtime-secrets.md"


def require(condition, message):
    if not condition:
        raise SystemExit(message)


def component_block(config, component):
    marker = f"'{component}' => ["
    start = config.index(marker)
    next_component = re.search(r"\n        '[^']+' => \[", config[start + len(marker):])
    end = start + len(marker) + next_component.start() if next_component else len(config)
    return config[start:end]


config = CONFIG.read_text(encoding="utf-8")
docs = DOCS.read_text(encoding="utf-8")

expected_vars = [
    "DB_DSN",
    "DB_USERNAME",
    "DB_PASSWORD",
    "WALLET_DB_DSN",
    "WALLET_DB_USERNAME",
    "WALLET_DB_PASSWORD",
    "REDIS_HOSTNAME",
    "REDIS_USERNAME",
    "REDIS_PASSWORD",
    "REDIS_PORT",
    "REDIS_DATABASE",
    "SENTRY_DSN",
]

for var in expected_vars:
    require(f"getenv('{var}')" in config, f"{var} must be read from the environment.")
    require(f"`{var}`" in docs, f"{var} must be documented.")

for component in ("db", "walletDb"):
    block = component_block(config, component)
    for key in ("dsn", "username", "password"):
        require(
            re.search(rf"'{key}'\s*=>\s*getenv\('[A-Z0-9_]+'\)", block),
            f"{component}.{key} must be env-backed.",
        )

redis = component_block(config, "redis")
for key in ("hostname", "password"):
    require(
        re.search(rf"'{key}'\s*=>\s*getenv\('[A-Z0-9_]+'\)", redis),
        f"redis.{key} must be env-backed.",
    )

log = component_block(config, "log")
require("'dsn' => getenv('SENTRY_DSN') ?: null" in log, "Sentry DSN must be env-backed.")

print("Production Railway runtime secret check passed.")
