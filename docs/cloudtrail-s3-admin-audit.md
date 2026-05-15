# CloudTrail S3 Admin Event Audit

Issue #55 Phase 7 asks the team to export CloudTrail events for the suspicious Apr 17-19 window and review whether service IAM users performed bucket-admin operations that should not be callable by application credentials.

This helper works on offline exports only. It does not call AWS, mutate IAM, rotate keys, or require contributors to see production credentials.

## Events Flagged

The audit filters the exact S3 bucket-admin event names called out in the incident plan:

- `PutBucketLifecycleConfiguration`
- `DeleteBucketCors`
- `PutBucketCors`
- `DeleteBucketPolicy`
- `PutBucketPolicy`
- `PutBucketReplicationConfiguration`
- `PutBucketLogging`
- `PutPublicAccessBlock`
- `DeletePublicAccessBlock`

## Inputs

Export the CloudTrail rows for the incident window and service users under review:

- `railway-s3-access`
- `n8n-s3-access`
- `mediaconverter`

The script accepts:

- CloudTrail JSON with a top-level `Records` array
- JSON arrays
- JSONL
- CSV exports with columns such as `eventTime`, `eventName`, `userName`, `accessKeyId`, `sourceIPAddress`, `userAgent`, `bucketName`, `region`, and `errorCode`

Do not commit raw CloudTrail exports to this repository.

## Usage

Generate a Markdown report:

```bash
node tools/audit-cloudtrail-s3-admin-events.mjs \
  --input /secure/path/cloudtrail-apr17-apr19.json
```

Generate CSV for filtering:

```bash
node tools/audit-cloudtrail-s3-admin-events.mjs \
  --input /secure/path/cloudtrail-apr17-apr19.json \
  --format csv
```

Add another watched service user:

```bash
node tools/audit-cloudtrail-s3-admin-events.mjs \
  --input /secure/path/cloudtrail-apr17-apr19.json \
  --watch-user custom-s3-automation-user
```

## Output

The report includes:

- Matching bucket-admin event counts
- Critical/high event counts
- Breakdowns by event name, IAM user, and bucket
- Per-event rows with `eventTime`, severity, event name, IAM user, access-key suffix, source IP, bucket, region, error code, and review note

Full access key IDs and secret-shaped values are redacted from output. The report keeps only the last four access-key characters so maintainers can match key suffixes already referenced in the incident plan.

## Verification

Run the synthetic fixture test:

```bash
node tools/check-cloudtrail-s3-admin-audit.mjs
```

The check verifies JSON and CSV input handling, suspicious-event filtering, severity classification, non-StudentHub bucket notes, failed-event notes, and key redaction.
