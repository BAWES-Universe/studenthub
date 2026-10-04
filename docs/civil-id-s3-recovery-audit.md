# Civil ID S3 Recovery Audit

Issue #55 Phase 8 asks for a way to identify Civil ID records whose database fields still point at `photos/<filename>` objects that may have been deleted by the accidental lifecycle rule.

This workflow is intentionally offline. It uses sanitized exports from the database and S3 inventories, then classifies each Civil ID file as present, recoverable from the legacy permanent prefix, recoverable from the temp bucket export, or missing.

## Inputs

Export candidate Civil ID references from the database:

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

Export object-key inventories separately for:

- `studenthub-uploads` permanent bucket
- `studenthub-public-anyone-can-upload-24hr-expiry` temp bucket, if still available for the incident window

The object inventory files can be one key per line, or CSV-style output where any cell containing a `photos/` or `candidate-civil-id/` key is used.

Do not place real candidate records or bucket exports in this repository.

## Usage

```bash
node tools/audit-civil-id-s3-objects.mjs \
  --candidates /secure/path/civil-id-candidates.csv \
  --permanent-objects /secure/path/studenthub-uploads-keys.txt \
  --temp-objects /secure/path/temp-bucket-keys.txt \
  --emit-copy-commands
```

Use CSV output when the result needs to be filtered or attached to an internal incident tracker:

```bash
node tools/audit-civil-id-s3-objects.mjs \
  --candidates /secure/path/civil-id-candidates.csv \
  --permanent-objects /secure/path/studenthub-uploads-keys.txt \
  --temp-objects /secure/path/temp-bucket-keys.txt \
  --format csv
```

## Statuses

- `present`: `studenthub-uploads/photos/<filename>` exists in the permanent bucket export.
- `recover_from_legacy`: `studenthub-uploads/candidate-civil-id/<filename>` exists and should be copied to `photos/<filename>`.
- `recover_from_temp`: the temp bucket export still contains the object and it should be copied to the permanent `photos/` prefix.
- `missing`: the object is absent from all supplied exports. Ask the candidate to re-upload; do not mass-clear database fields until the audit is complete.

## Verification

Run the fixture-based local check:

```bash
node tools/check-civil-id-s3-audit.mjs
```

The check creates synthetic candidate and S3 inventory exports under the OS temp directory and verifies all four statuses plus the generated copy commands.
