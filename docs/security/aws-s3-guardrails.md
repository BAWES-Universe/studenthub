# AWS S3 Service User Guardrails

This repo keeps application service users away from bucket-admin powers. Use these templates when creating or rotating StudentHub S3 service users after the Civil ID incident.

The templates are safe to review in GitHub because they contain no account IDs, ARNs for private users, access keys, secret keys, candidate data, or live bucket mutations.

## Files

- `docs/security/aws-s3-service-user-permissions-boundary.json` limits an S3 service user to object-level actions for the temp and permanent upload buckets, while explicitly denying bucket administration actions such as lifecycle, CORS, policy, logging, replication, public-access-block, website, ACL, and versioning changes.
- `docs/security/aws-s3-bucket-change-alerts.cloudformation.json` adds an EventBridge rule for CloudTrail S3 bucket-admin API calls and routes the events to an SNS topic provided by maintainers at deploy time.
- `tools/check-aws-s3-guardrails.mjs` validates that the templates keep the incident-sensitive denies, object-level allow list, and alert event coverage intact.

## Deploy Sequence

1. Provide the maintainer-owned `AlertSnsTopicArn` parameter outside this repo.
2. Create or update the SNS topic used for the technical alert channel.
3. Deploy `aws-s3-bucket-change-alerts.cloudformation.json` in the AWS account and region where CloudTrail management events are delivered.
4. Attach `aws-s3-service-user-permissions-boundary.json` as the permissions boundary for S3-only service users such as temp browser upload and permanent upload copy/delete users.
5. Keep identity policies narrower than the boundary. The boundary is a maximum allowed scope, not a replacement for per-user policies.
6. Tag each IAM user and key with `owner`, `service`, and `environment` so future CloudTrail events can be mapped to an accountable service.

## Validation

Run this before changing either template:

```bash
node tools/check-aws-s3-guardrails.mjs
```

The check fails if a protected bucket-admin action drops out of the boundary or EventBridge pattern, if an allow statement grants `s3:*`, or if an AWS access key shaped value is committed into these templates.

## Notes

- This does not rotate, deactivate, or delete any live keys.
- This does not enable versioning or change bucket policies directly.
- This does not replace a CloudTrail investigation. It gives maintainers a repeatable guardrail for the root-cause class: application service users should not be able to change lifecycle, CORS, policies, logging, replication, public access settings, or other bucket-level controls.
