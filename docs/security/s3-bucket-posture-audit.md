# S3 Bucket Posture Audit

This helper supports the Phase 9 bucket-hardening work from the AWS S3 remediation issue. It is intentionally offline-only: maintainers export S3 bucket posture data inside their trusted AWS environment, then run the local report without giving this repository live AWS credentials.

## What It Checks

The audit highlights posture gaps that matter for the `studenthub-*`, PlugN, and Wallet bucket incident class:

- permanent buckets without versioning
- destructive lifecycle rules on permanent buckets
- broad public-access settings or public ACL grants
- CORS rules that allow wildcard origins for write methods
- object ownership that is not bucket-owner-enforced
- missing access logging evidence
- missing `owner`, `service`, or `environment` tags

The report redacts account IDs and access-key-looking values before printing output.

## Export Shape

Create a private JSON file with one object per bucket. The script accepts either an array or an object with a `buckets` field:

```json
{
  "buckets": [
    {
      "name": "studenthub-uploads",
      "versioning": { "Status": "Enabled" },
      "publicAccessBlock": {
        "BlockPublicAcls": true,
        "IgnorePublicAcls": true,
        "BlockPublicPolicy": true,
        "RestrictPublicBuckets": true
      },
      "ownershipControls": {
        "Rules": [{ "ObjectOwnership": "BucketOwnerEnforced" }]
      },
      "logging": { "LoggingEnabled": { "TargetBucket": "studenthub-access-logs" } },
      "lifecycle": { "Rules": [] },
      "cors": { "CORSRules": [] },
      "tags": {
        "owner": "platform",
        "service": "studenthub",
        "environment": "prod"
      }
    }
  ]
}
```

Keep raw exports and generated reports out of public GitHub comments if they contain bucket ARNs, account IDs, internal hostnames, or incident notes.

## Usage

Markdown report:

```bash
node tools/audit-s3-bucket-posture.mjs private/s3-buckets.json
```

The command exits with status `2` when high or critical findings are present so maintainers can use it as a guardrail in a private review workflow.

CSV action plan:

```bash
node tools/audit-s3-bucket-posture.mjs private/s3-buckets.json --format csv --output private/s3-bucket-posture.csv
```

Audit additional bucket families:

```bash
node tools/audit-s3-bucket-posture.mjs private/s3-buckets.json --bucket-pattern bawes --bucket-pattern payroll
```

Run the synthetic regression check:

```bash
node tools/check-s3-bucket-posture-audit.mjs
```

## Safety Boundary

This tool does not call AWS, rotate keys, change bucket policy, enable versioning, modify lifecycle rules, or read candidate data. It only reads local JSON exports supplied by maintainers.
