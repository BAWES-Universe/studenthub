"""Validate that Railway database credentials stay runtime-configured."""


import os
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]

DEV_CONFIG = ROOT / "environments/dev-server-railway/common/config/main-local.php"
DEPLOYMENT_SCRIPTS = [
    ROOT / "environments/dev-server-railway/deployments/july_2025/3_july_2025_deployment.sh",
    ROOT / "environments/dev-server-railway/deployments/july_2025/29_july_2025_deployment.sh",
    ROOT / "environments/prod-railway/deployments/july_2025/3_july_2025_deployment.sh",
    ROOT / "environments/prod-railway/deployments/july_2025/29_july_2025_deployment.sh",
]
DOC = ROOT / "docs/railway-db-runtime-secrets.md"

DEV_CONFIG_ENV_VARS = [
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
]

SCRIPT_TOKENS = [
    'MYSQL_HOST="${MYSQL_HOST:-mysql.railway.internal}"',
    'MYSQL_PORT="${MYSQL_PORT:-3306}"',
    'MYSQL_USER="${MYSQL_USER:-root}"',
    'MYSQL_DATABASE="${MYSQL_DATABASE:-railway}"',
    ': "${MYSQL_PASSWORD:?MYSQL_PASSWORD must be set in Railway environment variables.}"',
    'MYSQL_PWD="$MYSQL_PASSWORD" mysql',
]

COMPONENT_PASSWORD_ENV = {
    "db": "DB_PASSWORD",
    "walletDb": "WALLET_DB_PASSWORD",
    "redis": "REDIS_PASSWORD",
}


def load_forbidden_secret_fragments():
    """Load optional secret fragments without committing them to this test."""
    fragments = []
    fragments.extend(os.environ.get("FORBIDDEN_SECRET_FRAGMENTS", "").splitlines())

    fragment_file = os.environ.get("FORBIDDEN_SECRET_FRAGMENTS_FILE")
    if fragment_file:
        fragments.extend(Path(fragment_file).read_text(encoding="utf-8").splitlines())

    return [fragment.strip() for fragment in fragments if fragment.strip()]


def read(path):
    """Read a repository file as UTF-8 text."""
    return path.read_text(encoding="utf-8")


def require(condition, message):
    """Fail the check with a clear message when a condition is false."""
    if not condition:
        raise SystemExit(message)


def component_block(config, name):
    """Return the top-level component block from the Yii config."""
    marker = f"        '{name}' => ["
    start = config.find(marker)
    require(start != -1, f"Dev Railway config must define the {name} component.")
    end = config.find("\n        ],", start)
    require(end != -1, f"Dev Railway config must close the {name} component.")
    return config[start:end]


def main():
    """Run the Railway runtime secret regression checks."""
    config = read(DEV_CONFIG)
    doc = read(DOC)
    script_contents = [read(path) for path in DEPLOYMENT_SCRIPTS]
    forbidden_fragments = load_forbidden_secret_fragments()
    checked_files = [
        (DEV_CONFIG, config),
        (DOC, doc),
        *zip(DEPLOYMENT_SCRIPTS, script_contents),
    ]

    for name in DEV_CONFIG_ENV_VARS:
        require(f"getenv('{name}')" in config, f"Dev Railway config must use getenv('{name}').")
        require(f"`{name}`" in doc, f"Docs must mention `{name}`.")

    for component, env_var in COMPONENT_PASSWORD_ENV.items():
        block = component_block(config, component)
        require(
            f"'password' => getenv('{env_var}')" in block,
            f"{component} password must come from getenv('{env_var}').",
        )
        require("'password' => '" not in block, f"{component} password must not be an inline literal.")
        require("'password' => \"" not in block, f"{component} password must not be an inline literal.")

    for path, content in zip(DEPLOYMENT_SCRIPTS, script_contents):
        for token in SCRIPT_TOKENS:
            require(token in content, f"{path} must contain {token!r}.")
        require('-p"$MYSQL_PASSWORD"' not in content, f"{path} must not pass MYSQL_PASSWORD on the command line.")
        require('MYSQL_PASSWORD="' not in content, f"{path} must not assign a committed MYSQL_PASSWORD.")

    for path, content in checked_files:
        match_count = sum(1 for fragment in forbidden_fragments if fragment in content)
        require(
            match_count == 0,
            f"{path} must not contain {match_count} runtime-provided forbidden secret fragment(s).",
        )

    print("Railway DB runtime secret check passed.")


if __name__ == "__main__":
    main()
