# S3 bucket guardrail audit helper

This helper supports issue #55 by producing an offline evidence report for the Civil ID upload bucket. It does not need AWS credentials when reviewing a PR: maintainers can export AWS CLI JSON once, commit or attach sanitized evidence, and run the checker locally.

## Evidence format

Create a JSON file with the bucket name and selected AWS CLI outputs:

```json
{
  "bucket": "studenthub-civil-id-uploads",
  "publicAccessBlock": {},
  "policyStatus": {},
  "encryption": {},
  "versioning": {},
  "ownershipControls": {},
  "lifecycle": {},
  "cors": {}
}
```

Suggested AWS commands:

```bash
aws s3api get-public-access-block --bucket "$BUCKET" > public-access-block.json
aws s3api get-bucket-policy-status --bucket "$BUCKET" > policy-status.json
aws s3api get-bucket-encryption --bucket "$BUCKET" > encryption.json
aws s3api get-bucket-versioning --bucket "$BUCKET" > versioning.json
aws s3api get-bucket-ownership-controls --bucket "$BUCKET" > ownership-controls.json
aws s3api get-bucket-lifecycle-configuration --bucket "$BUCKET" > lifecycle.json
aws s3api get-bucket-cors --bucket "$BUCKET" > cors.json
```

Copy each command output into the matching top-level property. Do not include raw object keys, IAM secrets, or full bucket policies in PR comments.

## Run

```bash
node tools/audit-s3-bucket-guardrails.mjs --input tools/fixtures/s3-bucket-guardrails.sample.json
node tools/audit-s3-bucket-guardrails.mjs --input tools/fixtures/s3-bucket-guardrails.sample.json --format json
node --test tools/s3-bucket-guardrails.test.mjs
```

## Guardrails checked

- Bucket evidence identifies the target bucket.
- Bucket policy status is not public.
- All four S3 Block Public Access flags are enabled.
- Default server-side encryption is configured.
- Versioning is enabled or explicitly called out as a warning.
- Bucket-owner-enforced object ownership is enabled.
- Incomplete multipart uploads are cleaned up by lifecycle policy.
- CORS does not allow wildcard origins with write methods.

The markdown report intentionally summarizes posture evidence without printing raw bucket policy statements.
