# n8n S3 workflow audit helper

Issue #55 calls out `n8n-s3-access` as a high-risk IAM user that may have been able to change S3 bucket lifecycle, CORS, policy, replication, logging, or public-access settings. This helper lets maintainers review exported n8n workflow and credential metadata locally without giving contributors live n8n, AWS, IAM, or candidate-data access.

## Inputs

Use maintainer-owned exports only. Do not commit real exports.

Supported workflow inputs:

- an n8n workflow export object with `nodes`
- an array of workflow objects
- an object with a `workflows` array
- an object with `data.workflows`

Supported credential inputs:

- an array of credential metadata objects
- an object with a `credentials` array
- an object with `data.credentials`
- the same combined export file used for `--workflows`

The tool scans workflow node metadata, node credentials references, credential names/types, and credential metadata for AWS/S3 indicators. It redacts access-key-looking values and secret-shaped fields before output.

## Commands

Markdown report:

```bash
node tools/audit-n8n-s3-workflows.mjs --workflows path/to/workflows.json --credentials path/to/credentials.json --format markdown --output n8n-s3-audit.md
```

CSV report:

```bash
node tools/audit-n8n-s3-workflows.mjs --workflows path/to/workflows.json --credentials path/to/credentials.json --format csv --output n8n-s3-audit.csv
```

If credentials are included in the workflow export, omit `--credentials`:

```bash
node tools/audit-n8n-s3-workflows.mjs --workflows path/to/n8n-export.json --format markdown
```

Regression check with synthetic data:

```bash
node tools/check-n8n-s3-workflow-audit.mjs
```

## Report fields

- `Risk`: `high` when bucket-admin operations are detected, `medium` for bucket/key references, `low` for generic AWS/S3 references.
- `Workflow`: workflow name and id from the export.
- `Node`: n8n node name.
- `Type`: node or credential type.
- `Signals`: redacted indicators such as StudentHub bucket names, n8n credential references, bucket-admin event names, and access-key suffixes.
- `Credential refs`: credential names/ids referenced by the workflow node.

## Bucket-admin signals

The high-risk event list mirrors the incident classes in issue #55:

- `PutBucketLifecycleConfiguration`
- `DeleteBucketCors`
- `PutBucketCors`
- `DeleteBucketPolicy`
- `PutBucketPolicy`
- `PutBucketReplicationConfiguration`
- `PutBucketLogging`
- `PutPublicAccessBlock`
- `DeletePublicAccessBlock`

## Safety boundary

This helper does not call AWS, IAM, S3, CloudTrail, or n8n APIs. It does not rotate keys, delete keys, mutate workflows, change bucket policy, or inspect candidate data. Run it only on local maintainer exports and keep raw exports private.

The generated report is designed for triage. Before disabling any workflow or IAM user, maintainers should confirm the workflow owner, server, schedule, source IP/user agent from CloudTrail, and production dependency status.
