# IAM Access Key Review Runbook

This runbook supports the StudentHub AWS remediation work in issue #55. It gives maintainers a repeatable offline review for IAM access keys without posting raw exports, full access key IDs, secret keys, account IDs, candidate data, or CloudTrail logs to GitHub.

The helper CLI matches exposed key suffixes from the incident notes, reports stale or inactive keys, and checks that service users have `owner`, `service`, and `environment` metadata before the monthly key review.

## Inputs

Create a private CSV with one row per access key:

```csv
user,access_key_id,status,created_at,last_used_at,last_used_service,last_used_region,owner,service,environment,notes
railway-s3-access,REDACTED_KEY_ID_ENDING_DOBNJ,active,2026-01-01,2026-04-17,s3,eu-west-2,platform,studenthub,production,private export
```

Do not commit the real CSV. Keep it in the private incident evidence folder.

## Run

```bash
node tools/audit-iam-access-keys.mjs \
  --keys /private/studenthub/iam-access-keys.csv \
  --suffixes DOBNJ,55KF,OFLT,XW5I,TMEZ \
  --as-of 2026-05-15 \
  --out /private/studenthub/iam-access-key-review.md
```

For spreadsheet review:

```bash
node tools/audit-iam-access-keys.mjs \
  --keys /private/studenthub/iam-access-keys.csv \
  --format csv \
  --out /private/studenthub/iam-access-key-review.csv
```

## Review Rules

- `rotate_and_deactivate`: active key matches a watched exposed suffix. Create a replacement key, deploy it through the runtime secret store, smoke test the related service, then deactivate the old key.
- `delete_after_evidence`: inactive key matches a watched suffix. Capture key suffix, IAM user, inactive status, and deletion timestamp for AWS Support.
- `delete_inactive_key`: inactive key has remained in IAM beyond the inactive threshold.
- `rotate_old_active_key`: active key is older than the rotation threshold.
- `review_or_disable_unused_key`: active key has no recent last-used evidence.
- `add_required_tags`: IAM user/key metadata is missing `owner`, `service`, or `environment`.

The report redacts key IDs to suffixes only, for example `...DOBNJ`.

## AWS Support Evidence

For each remediated suffix, capture only the following in the support package:

- IAM user name
- key suffix only
- old key deactivation timestamp
- old key deletion timestamp
- replacement variable name in Railway or AWS Secrets Manager
- smoke test result for the affected service

Never include full access key IDs, secret access keys, bearer tokens, candidate records, Civil ID images, database exports, or raw CloudTrail files in public GitHub comments.

## Local Regression Check

```bash
node tools/check-iam-access-key-review.mjs
```

The check uses synthetic fixture data and verifies that Markdown and CSV reports include the expected actions without leaking full key IDs.
