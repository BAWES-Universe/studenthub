# AWS Support Evidence Package

Issue #55 includes a final evidence package for requesting AWS restriction review after the StudentHub S3/IAM remediation is complete. Keep raw screenshots, CloudTrail exports, IAM CSV files, Railway screens, candidate records, and full key IDs in a private incident folder. This helper only produces a redacted Markdown summary that can be reviewed before sending to AWS Support.

## Usage

Prepare a private JSON manifest using the sample shape in `tools/fixtures/aws-support-evidence-package.sample.json`, then run:

```bash
node tools/build-aws-support-evidence-package.mjs private/aws-support-evidence.json > private/aws-support-evidence.md
```

The generated report redacts:

- 12-digit AWS account IDs
- AWS access-key-looking values
- long secret-like token strings

It preserves short key suffixes such as `FZMN`, `4T67K`, `ODY2X`, and `WCUM` because issue #55 uses suffixes as the public-safe reference format.

## Manifest Sections

The manifest should contain only evidence summaries and local/private file references:

- `deletedKeys`: inactive key suffixes deleted after screenshots were captured
- `rotatedKeys`: exposed or over-permissioned key suffixes rotated or replaced
- `environmentVariables`: replacement variable names configured in Railway/secret stores
- `bucketControls`: S3 lifecycle, CORS, versioning, logging, replication, and public-access evidence
- `smokeTests`: post-remediation upload, remove, replace, staff-view, and key-deactivation tests
- `cloudTrail`: investigation summaries with source IP, user agent, event, bucket, and key suffix only
- `iamReviews`: least-privilege/access-analyzer review summaries
- `supportNotes`: remaining limitations, private attachment locations, or follow-up owner notes

Do not put full keys, secret keys, bearer tokens, raw CloudTrail JSON, candidate Civil ID values/images, phone numbers, account IDs, or payment/tax data into the manifest.

## Validation

Run the local regression check before committing changes to this helper:

```bash
node tools/check-aws-support-evidence-package.mjs
node --check tools/build-aws-support-evidence-package.mjs
node --check tools/check-aws-support-evidence-package.mjs
```
