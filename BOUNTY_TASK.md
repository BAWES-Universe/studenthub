# Bounty Objective: 🔐 AWS S3 Security Remediation & Civil ID Upload Fix — IAM Key Rotation, Bucket Hardening & Backend Patches
Target: BAWES-Universe/studenthub Issue #55
URL: https://github.com/BAWES-Universe/studenthub/issues/55

## Issue Description
# AWS S3 Security Remediation & Civil ID Upload Fix

> **⚠️ Sensitive handling note**
> This issue intentionally uses IAM user names and key suffixes only. Do **not** paste full access keys, secret keys, bearer tokens, or private candidate data into comments on this issue.

---

## Background

A destructive S3 lifecycle rule named `PressureDelete3Days` was discovered enabled on the **permanent** `studenthub-uploads` bucket, scoped to the entire bucket (not just temp files). This rule has now been **disabled**, but files deleted before that point may be unrecoverable as bucket versioning was not enabled at the time.

Additionally, several IAM service users (`railway-s3-access`, `n8n-s3-access`, `mediaconverter`) appear to have had bucket-admin level permissions they should never have had — including the ability to modify lifecycle rules, CORS, bucket policies, replication, and logging. These are the root-cause risk, not just the exposed keys themselves.

**CORS has been added to the temp upload bucket** (`studenthub-public-anyone-can-upload-24hr-expiry`) and new Civil ID uploads are working again. The permanent bucket lifecycle rule is disabled. What remains is key rotation, code patches, backend fixes, and hardening.

---

## Upload Architecture (for contributor context)

```
Browser / Candidate app
  -> GET /aws/config
  -> PUT file directly to temp S3 bucket (studenthub-public-anyone-can-upload-24hr-expiry)
  -> POST filename to backend
  -> Backend copies file to permanent S3 bucket (studenthub-uploads)
  -> DB stores filename only
  -> Frontend builds permanent image URL from bucket URL + prefix + filename
```

| Upload type | Storage path |
|---|---|
| Civil ID front/back | Temp S3 → `studenthub-uploads/photos/<filename>` |
| Resume | Temp S3 → `studenthub-uploads/candidate-resume/` |
| Video | Temp S3 → `studenthub-uploads` + MediaConvert job |
| Personal photo | Temp S3 → Cloudinary (older backend flow) |

---

## ✅ Already Completed

- [x] CORS added to `studenthub-public-anyone-can-upload-24hr-expiry` — browser direct-to-S3 uploads work again
- [x] `PressureDelete3Days` lifecycle rule disabled on `studenthub-uploads`
- [x] Permanent bucket path confirmed: Civil ID files expected at `photos/<filename>`
- [x] Old inactive keys `FZMN` (textract-access) and `4T67K` (public-environment-s3-access) confirmed with no active references — ready for deletion
- [x] Civil ID edit/remove 500 endpoints identified

---

## 🔴 Phase 1 — Delete Safe Inactive Keys (Ready Now)

These keys are already inactive and have no confirmed active references. Screenshot IAM evidence before deleting.

- [ ] Delete old inactive key `FZMN` from IAM user `textract-access` (replacement Textract key already active in Railway)
- [ ] Delete old inactive key `4T67K` from IAM user `public-environment-s3-access` (replacement temp key active, used by `/aws/config`)
- [ ] Record key IDs, IAM user names, and deletion timestamps for AWS Support evidence package

---

## 🔴 Phase 2 — Fix Civil ID Edit/Remove 500 Errors (Backend — High Priority)

**Files to patch:**

- `candidate/modules/v1/controllers/AccountController.php`
- `common/models/Candidate.php`
- `common/components/S3ResourceManager.php`

**Required fixes:**

- [ ] Patch `DELETE /v1/account/remove-civil-photo-front` and `/remove-civil-photo-back` to tolerate missing S3 objects — delete `photos/<filename>` best-effort only, clear DB field, and mark verification needed (no 500)
- [ ] Patch `POST /v1/account/update-civil-id-expiry-date` to validate `civil_id` and `civil_expiry_date`, wrap save in try/catch, return `operation:error` instead of raw 500
- [ ] Fix `Candidate::deleteFile("civil-id")` prefix — change from `candidate-civil-id/` to `photos/` (wrong prefix causing failed deletes)
- [ ] Fix `Candidate::deleteFile("civil-id")` — stop adding errors to the unrelated `candidate_resume` field
- [ ] Patch `Candidate::updateCivilId()` to: copy new file first → verify destination → delete old file after success only → log failures
- [ ] Add `S3ResourceManager::fileExists()` headObject wrapper if missing
- [ ] Add `route/candidate_id/filename` logging for copy/delete failures

---

## 🔴 Phase 3 — Frontend Error Handling Fixes

- [ ] **Candidate app:** Handle remove-civil-photo API errors locally — show toast/message, prevent global 500 route trap
- [ ] **Candidate app:** Allow back navigation from `/civil-id?fromProfile=1` to profile even after partial failure
- [ ] **Staff app:** Add error handler to `loadCandidateDetail()` so candidate profile does not become a blank page
- [ ] **Staff app:** Fix back Civil ID template condition — check `candidate_civil_photo_back`, not the front field
- [ ] **Staff app:** Show "Civil ID image unavailable — request re-upload" when image returns 403 or is missing

---

## 🔴 Phase 4 — Patch Hardcoded Keys Out of Code (ODY2X & WCUM)

> **Do not delete these keys until code is patched, deployed, and tested.**

**ODY2X** — temp S3 backend credentials, still hardcoded in `common/config/main.php`

- [ ] Patch `common/config/main.php` `temporaryBucketResourceManager` to use env vars: `AWS_TEMP_BUCKET_KEY` / `AWS_TEMP_BUCKET_SECRET`
- [ ] Search and patch any microservice or PlugN/Wallet references

**WCUM** — permanent S3 credentials (`railway-s3-access`), still in prod Railway configs and microservices

- [ ] Patch `environments/prod-railway/common/config/main-local.php` `resourceManager` to use env vars: `AWS_PERMANENT_S3_ACCESS_KEY_ID` / `AWS_PERMANENT_S3_SECRET_ACCESS_KEY`
- [ ] Add Railway env vars: `AWS_PERMANENT_S3_ACCESS_KEY_ID`, `AWS_PERMANENT_S3_SECRET_ACCESS_KEY`, `AWS_PERMANENT_S3_REGION=eu-west-2`, `AWS_PERMANENT_S3_BUCKET=studenthub-uploads`
- [ ] Patch PlugN, Wallet, Yeastar voicemail, and dev-server configs that still reference WCUM
- [ ] **Remove bucket-admin permissions** from `railway-s3-access` immediately — restrict to object-level `GetObject`, `PutObject`, `PutObjectAcl`, `CopyObject`, `DeleteObject`, `ListBucket` on `studenthub-uploads` only

---

## 🔴 Phase 5 — Rotate/Delete ODY2X & WCUM

After Phase 4 deploy and tests pass:

- [ ] Deactivate `ODY2X` → retest all upload flows → delete `ODY2X`
- [ ] Deactivate `WCUM` → retest permanent copy flows → delete `WCUM`
- [ ] Record key IDs, timestamps, and test evidence for AWS Support

---

## 🟠 Phase 6 — Investigate DOBNJ, n8n-s3-access & Extra Keys

**DOBNJ** — SQS/EventManager/Yeastar-related, still in committed configs

- [ ] Find IAM user by key suffix `DOBNJ`
- [ ] Confirm active use by StudentHub production, PlugN, Yeastar PBX/websocket, or any worker/SQS service
- [ ] If unused: remove configs and delete. If used: replace with env vars, restrict to exact SQS queue ARNs only, rotate

**n8n-s3-access** — High suspicion, associated with bucket lifecycle/CORS/policy changes

- [ ] Identify owner, server, and n8n workflow using this IAM user
- [ ] Search CloudTrail for all bucket-admin events by this user across all buckets (target date range: Apr 17–19 and surrounding period)
- [ ] If not production-critical, disable keys immediately and preserve evidence
- [ ] Remove all bucket-admin permissions
- [ ] If n8n genuinely needs S3 access, create a new minimal scoped user

**Extra exposed keys to review:** `55KF`, `OFLT` (MediaConvert), `XW5I` (SES SMTP), `TMEZ` (process-id-request S3)

- [ ] Search GitHub repos and IAM for each key suffix
- [ ] Rotate if active; restrict permissions to least-privilege; delete if unused

---

## 🟡 Phase 7 — CloudTrail Investigation (Compromise Assessment)

> Treat `railway-s3-access`, `n8n-s3-access`, and `mediaconverter` as **over-permissioned at minimum and potentially compromised** until CloudTrail confirms known source IPs, user agents, and expected automation.

Suspicious event types to search for (none of these should have been callable by app service users):

| Event | Why it matters |
|---|---|
| `PutBucketLifecycleConfiguration` | Can delete permanent S3 objects automatically — likely explains missing Civil ID files |
| `DeleteBucketCors` / `PutBucketCors` | Can break or loosen browser uploads |
| `DeleteBucketPolicy` / `PutBucketPolicy` | Can expose or block S3 data |
| `PutBucketReplicationConfiguration` | Can disable backups |
| `PutBucketLogging` | Can alter audit logging |
| `PutPublicAccessBlock` / `DeletePublicAccessBlock` | Can change public-read behavior |

- [ ] Export CloudTrail events for Apr 17–19 (and surrounding suspicious period) for `railway-s3-access`, `n8n-s3-access`, `mediaconverter`
- [ ] For each event record: `eventTime`, `eventName`, `userName`, `accessKeyId` suffix, `sourceIPAddress`, `userAgent`, `bucketName`, `region`, `errorCode`
- [ ] Confirm whether the lifecycle script touched only `studenthub-uploads` or also PlugN/Wallet buckets
- [ ] Ask team who owns `n8n-s3-access` and which n8n workflow or server used it

---

## 🟡 Phase 8 — Missing Civil ID Data Audit & Recovery

Old DB records reference `photos/<filename>` objects that no longer exist in S3 (likely deleted by the lifecycle rule).

- [ ] Run the following query to export all affected Civil ID filenames:

```sql
SELECT candidate_id, 'front' AS side, candidate_civil_photo_front AS filename,
  CONCAT('photos/', candidate_civil_photo_front) AS expected_s3_key, candidate_updated_at
FROM candidate
WHERE candidate_civil_photo_front IS NOT NULL AND candidate_civil_photo_front <> ''
UNION ALL
SELECT candidate_id, 'back' AS side, candidate_civil_photo_back AS filename,
  CONCAT('photos/', candidate_civil_photo_back) AS expected_s3_key, candidate_updated_at
FROM candidate
WHERE candidate_civil_photo_back IS NOT NULL AND candidate_civil_photo_back <> ''
ORDER BY candidate_updated_at DESC;
```

- [ ] For each filename, check `studenthub-uploads/photos/<filename>` and legacy path `studenthub-uploads/candidate-civil-id/<filename>`
- [ ] If found under legacy prefix, copy to `photos/<filename>`
- [ ] If still in temp bucket within lifecycle window, copy to permanent
- [ ] If missing everywhere — flag candidate for re-upload (do **not** mass-clear DB fields until audit is complete)

---

## 🟢 Phase 9 — Bucket Hardening & Guardrails

- [ ] Enable versioning on `studenthub-uploads` to protect future accidental deletions
- [ ] Audit all `studenthub-*` buckets plus PlugN/Wallet buckets for lifecycle rules, CORS, policies, ACLs, public access settings
- [ ] Set up EventBridge/CloudWatch alerts for: `PutBucketLifecycleConfiguration`, `DeleteBucketCors`, `PutBucketCors`, `DeleteBucketPolicy`, `PutBucketPolicy`, `PutPublicAccessBlock`, `PutBucketReplicationConfiguration`
- [ ] Route bucket-admin change alerts to Slack/Discord `#tech` and email
- [ ] Run IAM Access Analyzer for all prod buckets and service users
- [ ] Enable GitHub secret scanning + push protection across BAWES-Universe repos
- [ ] Remove committed `.env` files containing AWS keys from repos (and history where practical)
- [ ] Move secrets to Railway env vars or AWS Secrets Manager — no full keys in Discord or GitHub issues
- [ ] Add `owner` / `service` / `environment` tags to every IAM user and key
- [ ] Create a service-user **permissions boundary** that explicitly denies all bucket-admin actions for service IAM users
- [ ] Schedule monthly IAM access key review

---

## Least-Privilege Permissions Model (Reference)

| User type | Allowed | Must NOT allow |
|---|---|---|
| Temp browser upload user | `s3:PutObject`, `s3:PutObjectAcl`, `s3:GetObject`, `s3:AbortMultipartUpload`, `s3:ListMultipartUploadParts` on temp bucket only | Any access to `studenthub-uploads`; any bucket-admin actions |
| Permanent S3 app user | `s3:GetObject`, `s3:PutObject`, `s3:PutObjectAcl`, `s3:CopyObject`, `s3:DeleteObject`, `s3:ListBucket` on `studenthub-uploads` only | Lifecycle/CORS/policy/logging/replication/website/public-access-block changes |
| Textract user | `textract:DetectDocumentText` + minimal S3 read | S3 writes, S3 deletes, bucket admin |
| MediaConvert user | MediaConvert job actions + exact S3 input/output object paths | Bucket-level admin |
| SQS/EventManager user | `sqs:SendMessage/ReceiveMessage/DeleteMessage/GetQueueAttributes` on exact queue ARNs | S3 access unless explicitly needed |

---

## Post-Fix Test Checklist

| Test | Steps | Expected result |
|---|---|---|
| New candidate signup | Create profile with personal photo + Civil ID front/back | Profile completes, DB fields update, files in temp and permanent buckets |
| Existing candidate Civil ID remove | Remove front/back from profile edit | No 500; DB field clears; user returns to profile |
| Existing candidate Civil ID replace | Upload new front/back | No 500; files in `studenthub-uploads/photos/`; expiry and ID update |
| Staff profile view | Open candidate with missing/old Civil ID | No blank page; friendly missing-file notice |
| S3 permanent copy | Check `studenthub-uploads/photos/` | Expected new filenames present |
| Key deactivation dry run | Deactivate one old key at a time after patch | Relevant flows still work before deletion |

---

## AWS Support Evidence Package (to request restriction removal)

- [ ] List of exposed key suffixes remediated with deletion timestamps
- [ ] Screenshots of old `FZMN` and `4T67K` inactive/deleted and replacements active
- [ ] PR links removing hardcoded `ODY2X`/`WCUM`/`DOBNJ` and extra keys from repos
- [ ] Railway env var screenshots showing replacement variable names only (not secrets)
- [ ] S3 temp bucket CORS fix screenshot
- [ ] `studenthub-uploads` lifecycle rule disabled screenshot
- [ ] Versioning enabled screenshot
- [ ] Smoke test screenshots/logs for new profile creation and Civil ID upload post-remediation
- [ ] CloudTrail investigation results showing source IP/user agent/automation owner

---

> **Prepared by:** Mishari (2026-05-14) | **Scope:** StudentHub AWS Account & Related Repositories
> 
> This issue is eligible for an **Algora bounty**. Contributors are welcome to pick up individual phases. Comment below to claim a phase before starting work.


## Instructions for Kuro (Autonomous Solver):
1. Use skills: `systematic-debugging` and `code-review-and-quality`.
2. Inspect the repository structure, locate relevant files, and understand the bug/feature.
3. Apply the minimal, robust fix or implementation.
4. Run any existing test suites or linters in the workspace.
5. Ensure the fix adheres to the project's existing code style.
6. When done, output a summary of files modified and verification results.
