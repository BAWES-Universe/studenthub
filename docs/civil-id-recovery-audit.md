# Civil ID Recovery Audit

This runbook supports the Phase 8 Civil ID data audit without exposing candidate data in GitHub, chat, or public logs.

The audit is intentionally offline:

- The script does not connect to the production database.
- The script does not call AWS, S3, IAM, or CloudTrail.
- The script does not clear or mutate database fields.
- Maintainers run the database and S3 export commands in their own trusted environment and keep the exported files private.

## Export Candidate References

Run the query from the bounty issue in a trusted database session and save the result as CSV with these headers:

```text
candidate_id,side,filename,expected_s3_key,candidate_updated_at
```

Do not upload the CSV to GitHub if it contains candidate IDs, filenames, or other operational data.

## Export S3 Key Inventories

Create local key-list files for:

- Current permanent bucket keys under `studenthub-uploads/photos/`
- Legacy keys under `studenthub-uploads/candidate-civil-id/`
- Temp upload bucket keys that may still be within the lifecycle window

Each input may be either a newline-delimited key list or a CSV file with a `Key` or `s3_key` column. Extra CSV columns are ignored.

## Generate Recovery Plan

```bash
php tools/audit-civil-id-files.php \
  --candidate-csv=/private/path/candidate-civil-id-export.csv \
  --permanent-keys=/private/path/studenthub-uploads-photos.csv \
  --legacy-keys=/private/path/studenthub-uploads-legacy-civil-id.csv \
  --temp-keys=/private/path/temp-bucket-keys.csv \
  --output=/private/path/civil-id-recovery-plan.json
```

The output classifies each row as:

- `permanent_present`: expected `photos/<filename>` object exists.
- `copy_from_legacy`: legacy `candidate-civil-id/<filename>` object exists and should be copied to `photos/<filename>`.
- `copy_from_temp`: temp-bucket object exists and should be copied to `photos/<filename>`.
- `request_reupload`: no matching object was found in the supplied inventories; request candidate re-upload after manual review.
- `invalid_empty_filename`: export row needs manual review.

## Safety Rules

- Verify each source object before copying.
- Copy before deleting anything.
- Do not mass-clear database fields from this report.
- Keep raw CSVs and recovery reports out of public PRs and comments.
- Only publish aggregate counts or redacted examples.
