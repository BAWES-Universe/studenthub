# CloudTrail S3 Admin Event Audit

Issue #55 asks for a CloudTrail review of service users that should not be able to change S3 bucket controls. This helper keeps that review offline: maintainers export CloudTrail JSON privately, run the script locally, and share only the redacted summary.

The script focuses on high-risk bucket administration events, including lifecycle, CORS, policy, replication, logging, public access block, ACL, ownership, versioning, and website configuration changes.

## Usage

Run against one CloudTrail JSON file:

```bash
node tools/audit-cloudtrail-s3-admin-events.mjs cloudtrail-export.json
```

Run against a directory of JSON exports and write CSV:

```bash
node tools/audit-cloudtrail-s3-admin-events.mjs ./cloudtrail-exports \
  --format csv \
  --out cloudtrail-s3-admin-events.csv
```

By default the audit filters to:

- buckets beginning with `studenthub-`
- IAM users `railway-s3-access`, `n8n-s3-access`, and `mediaconverter`

Useful options:

```bash
# Include every bucket in the export.
node tools/audit-cloudtrail-s3-admin-events.mjs ./cloudtrail-exports --all-buckets

# Include every actor in the export.
node tools/audit-cloudtrail-s3-admin-events.mjs ./cloudtrail-exports --all-users

# Audit an additional bucket prefix or user.
node tools/audit-cloudtrail-s3-admin-events.mjs ./cloudtrail-exports \
  --bucket-prefix studenthub- \
  --bucket-prefix plugn- \
  --user railway-s3-access \
  --user n8n-s3-access
```

## Supported Exports

The helper accepts:

- CloudTrail `Records` arrays
- raw arrays of event records
- single CloudTrail event objects
- Event History records containing a stringified `CloudTrailEvent`

It walks directories recursively and reads `.json` files only.

## Privacy Boundary

Do not commit raw CloudTrail exports. They can contain source IPs, user agents, resource names, and account metadata.

The report intentionally prints only the last four characters of any `accessKeyId`. It never needs full AWS access keys, secret keys, private candidate data, bucket object contents, or live AWS credentials.

## Verification

The repository includes a synthetic fixture with no real account data:

```bash
node tools/check-cloudtrail-s3-admin-audit.mjs
```

Expected output:

```text
CloudTrail S3 admin audit check passed.
```
