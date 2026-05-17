#!/usr/bin/env python3
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]

CONTROLLERS = [
    "admin/modules/v1/controllers/AuthController.php",
    "company/modules/v1/controllers/AuthController.php",
    "staff/modules/v1/controllers/AuthController.php",
    "manager/modules/v1/controllers/AuthController.php",
    "candidate/modules/v1/controllers/AuthController.php",
]


def read(path):
    return (ROOT / path).read_text()


def assert_contains(path, text):
    content = read(path)
    if text not in content:
        raise AssertionError(f"{path} does not contain {text!r}")


def assert_not_contains(path, text):
    content = read(path)
    if text in content:
        raise AssertionError(f"{path} still contains {text!r}")


def main():
    verifier = "common/components/GoogleIdTokenVerifier.php"

    for expected in [
        "GOOGLE_OAUTH_CLIENT_IDS",
        "$response->aud",
        "$response->iss",
        "$response->exp",
        "$response->email",
        "email_verified",
        "http_build_query(['id_token' => $idToken])",
    ]:
        assert_contains(verifier, expected)

    assert_contains("common/config/main.php", "'googleIdTokenVerifier'")
    assert_contains("common/config/main.php", "GOOGLE_OAUTH_CLIENT_IDS")

    for controller in CONTROLLERS:
        assert_contains(controller, "Yii::$app->googleIdTokenVerifier->verify($token)")
        assert_not_contains(controller, "tokeninfo?id_token=")
        assert_not_contains(controller, "json_decode(curl_exec($ch))")


if __name__ == "__main__":
    main()
