# Phase 8 — Missing Civil ID Data Audit & Recovery

This document details the audit and recovery process for candidate Civil ID photos referenced in the database that were affected by legacy S3 lifecycle rules (such as `PressureDelete3Days`).

---

## 1. Background & Scope

Under earlier lifecycle policies on `studenthub-uploads`, temporary deletion rules inadvertently impacted permanent or legacy-located Civil ID documents.

Phase 8 implements automated cross-storage audit and remediation:
1. **Canonical target**: `studenthub-uploads/photos/<filename>`
2. **Legacy prefix**: `studenthub-uploads/candidate-civil-id/<filename>`
3. **Temporary upload bucket**: `<filename>` or `photos/<filename>`
4. **Missing everywhere**: Flag candidates for re-upload while **preserving all database fields** (no premature mass-clearing).

---

## 2. SQL Export Query

To query all active candidate Civil ID references (both front and back):

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

---

## 3. Running via Yii2 Console

Inside the application container (e.g., Docker or Railway):

### A. Dry-Run Simulation (Default)
Safe preview mode; checks existence across all buckets without modifying any objects:
```bash
./yii resource/audit-civil-id-recovery
# or explicitly:
./yii resource/audit-civil-id-recovery --dryRun=1
```

### B. Live Remediation Execution
Executes cross-bucket and cross-prefix copy operations to restore files to `photos/<filename>`:
```bash
./yii resource/audit-civil-id-recovery --dryRun=0
```

### Options:
- `--dryRun=1|0` (`-d 1|0`): 1 for dry-run simulation, 0 for live execution (default: `1`).
- `--outputCsv=/path/to/report.csv` (`-o`): Custom path for the full audit CSV report.

---

## 4. Running via Python CLI Tool

A standalone Python 3 CLI tool is provided at `scripts/audit-civil-id-recovery.py`:

```bash
# Display the Phase 8 SQL export query
python scripts/audit-civil-id-recovery.py --print-sql

# Run a dry-run audit using an exported CSV
python scripts/audit-civil-id-recovery.py --input-csv exported_candidates.csv

# Run live remediation using an exported CSV
python scripts/audit-civil-id-recovery.py --input-csv exported_candidates.csv --execute
```

### CLI Arguments:
| Argument | Description | Default |
|---|---|---|
| `--input-csv` | Path to CSV exported from candidate Civil ID query | None |
| `--input-json` | Path to JSON exported from candidate query | None |
| `--perm-bucket` | Permanent S3 bucket name | `studenthub-uploads` |
| `--temp-bucket` | Temporary upload S3 bucket name | `studenthub-uploads-temp` |
| `--execute` | Execute S3 copy operations | `False` (Dry-run by default) |
| `--output-csv` | Output full audit CSV report path | `console/runtime/civil_id_audit_report.csv` |
| `--output-reupload` | Output CSV listing candidates needing re-upload | `console/runtime/candidates_requiring_reupload.csv` |
| `--output-json` | Output full JSON report path | `console/runtime/civil_id_audit_report.json` |

---

## 5. Audit Status Outcomes

| Status Code | Meaning | Action Taken |
|---|---|---|
| `OK_CANONICAL` | Object already exists at `photos/<filename>` | None (Already canonical) |
| `RECOVERED_LEGACY` | Found under legacy `candidate-civil-id/<filename>` | Copied to `photos/<filename>` |
| `RECOVERED_TEMP` | Found in temporary upload bucket | Copied to permanent `photos/<filename>` |
| `MISSING_REUPLOAD_REQUIRED` | Missing from canonical, legacy, and temp storage | Flagged for user re-upload (DB preserved) |
| `ERROR` | Malformed input or S3 permission error | Logged with error detail |

---

## 6. Verification & Automated Tests

To run the automated test suite verifying all audit paths, dry-run safety, and reporting:

```bash
python tests/test-audit-civil-id-recovery.py
```
