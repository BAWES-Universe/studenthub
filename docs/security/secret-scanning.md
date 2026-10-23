# Secret Scanning Guardrails

This repository is in the middle of credential cleanup. The workflow in
`.github/workflows/secret-scan.yml` adds a code-level guardrail so new
verified secrets are caught before they are merged.

## What The Workflow Does

- Scans pull request changes with TruffleHog.
- Scans commits pushed to `master`.
- Reports only verified secrets to reduce false positives.
- Scans the new change range instead of the entire repository history, so
  ongoing remediation work is not blocked by legacy findings that are being
  removed in separate PRs.

## Maintainer Settings To Enable

GitHub Actions cannot enable repository-level protection settings by itself.
Maintainers should also enable the native GitHub controls for this repository:

1. Open **Settings** -> **Code security and analysis**.
2. Enable **Secret scanning**.
3. Enable **Push protection** for secret scanning.
4. Keep any approved bypasses documented with the key suffix only, never the
   full secret value.

## Handling A Detection

If the workflow or GitHub push protection reports a secret:

1. Treat the credential as exposed.
2. Rotate or revoke it in the owning service before merging.
3. Remove the value from the commit and force-push a clean replacement commit.
4. Reference only the service name and final four or five characters of the key
   in GitHub issues or pull requests.

Do not paste full access keys, secret keys, bearer tokens, SMTP credentials,
OAuth client secrets, candidate data, or screenshots containing secret values
into GitHub comments, pull requests, logs, or chat tools.
