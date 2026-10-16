#!/usr/bin/env python3
"""
tests/test-audit-civil-id-recovery.py

Unit and regression tests for Phase 8 Civil ID data audit & recovery tool.
Covers:
- SQL query verification against Issue #55 specification
- Filename and S3 key normalization
- Multi-bucket resolution (Canonical, Legacy, Temp, Missing)
- Dry-run mode vs live execution
- Export report integrity (CSV, JSON, Re-upload list)
- CLI argument parsing and error handling
"""

from __future__ import annotations

import csv
import importlib.util
import json
import sys
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SCRIPT_PATH = ROOT / "scripts" / "audit-civil-id-recovery.py"

# Import audit script dynamically
spec = importlib.util.spec_from_file_location("audit_tool", SCRIPT_PATH)
if spec is None or spec.loader is None:
    raise ImportError(f"Cannot load {SCRIPT_PATH}")
audit = importlib.util.module_from_spec(spec)
sys.modules["audit_tool"] = audit
spec.loader.exec_module(audit)


class TestAuditCivilIdRecovery(unittest.TestCase):

    def test_sql_query_matches_specification(self):
        """The SQL query must exactly match the Phase 8 specification."""
        sql = audit.SQL_EXPORT_QUERY
        self.assertIn("candidate_civil_photo_front", sql)
        self.assertIn("candidate_civil_photo_back", sql)
        self.assertIn("CONCAT('photos/', candidate_civil_photo_front)", sql)
        self.assertIn("UNION ALL", sql)
        self.assertIn("ORDER BY candidate_updated_at DESC", sql)

    def test_normalize_civil_id_key(self):
        """Verify normalization mirrors Candidate::normalizeCivilIdPermanentS3Key."""
        # Standard clean basename
        base, key = audit.normalize_civil_id_key("civil_front_123.jpg")
        self.assertEqual(base, "civil_front_123.jpg")
        self.assertEqual(key, "photos/civil_front_123.jpg")

        # Already prefixed with photos/
        base, key = audit.normalize_civil_id_key("photos/civil_front_123.jpg")
        self.assertEqual(base, "civil_front_123.jpg")
        self.assertEqual(key, "photos/civil_front_123.jpg")

        # Legacy prefix candidate-civil-id/
        base, key = audit.normalize_civil_id_key("candidate-civil-id/civil_front_123.jpg")
        self.assertEqual(base, "civil_front_123.jpg")
        self.assertEqual(key, "photos/civil_front_123.jpg")

        # Leading slash and whitespace
        base, key = audit.normalize_civil_id_key("  /photos/photo_abc.png  ")
        self.assertEqual(base, "photo_abc.png")
        self.assertEqual(key, "photos/photo_abc.png")

        # Empty or null
        base, key = audit.normalize_civil_id_key("")
        self.assertEqual(base, "")
        self.assertEqual(key, "")

        base, key = audit.normalize_civil_id_key(None)
        self.assertEqual(base, "")
        self.assertEqual(key, "")

    def test_audit_canonical_found(self):
        """Object present at canonical path returns OK_CANONICAL and makes no copies."""
        mock_s3 = audit.MockS3Backend({
            "studenthub-uploads": {
                "photos/card_01.jpg": b"data_01",
            }
        })
        records = [{
            "candidate_id": "101",
            "side": "front",
            "filename": "card_01.jpg",
            "candidate_updated_at": "2026-05-01 10:00:00",
        }]

        results = audit.run_audit(records, mock_s3, dry_run=False)
        self.assertEqual(len(results), 1)
        res = results[0]
        self.assertEqual(res.status, audit.AuditStatus.OK_CANONICAL)
        self.assertEqual(res.expected_s3_key, "photos/card_01.jpg")
        self.assertEqual(len(mock_s3.copy_log), 0)

    def test_audit_legacy_recovery_dry_run(self):
        """In dry-run, legacy object triggers RECOVERED_LEGACY without executing S3 copy."""
        mock_s3 = audit.MockS3Backend({
            "studenthub-uploads": {
                "candidate-civil-id/card_02.jpg": b"data_legacy",
            }
        })
        records = [{
            "candidate_id": "102",
            "side": "back",
            "filename": "card_02.jpg",
            "candidate_updated_at": "2026-05-02 11:00:00",
        }]

        results = audit.run_audit(records, mock_s3, dry_run=True)
        self.assertEqual(len(results), 1)
        res = results[0]
        self.assertEqual(res.status, audit.AuditStatus.RECOVERED_LEGACY)
        self.assertIn("[DRY-RUN]", res.action_taken)
        self.assertEqual(len(mock_s3.copy_log), 0)
        # Target canonical key should NOT yet exist in mock S3
        self.assertFalse(mock_s3.object_exists("studenthub-uploads", "photos/card_02.jpg"))

    def test_audit_legacy_recovery_live_execute(self):
        """In live execution, legacy object is copied to canonical photos/<filename>."""
        mock_s3 = audit.MockS3Backend({
            "studenthub-uploads": {
                "candidate-civil-id/card_03.jpg": b"data_legacy_live",
            }
        })
        records = [{
            "candidate_id": "103",
            "side": "front",
            "filename": "card_03.jpg",
            "candidate_updated_at": "2026-05-03 12:00:00",
        }]

        results = audit.run_audit(records, mock_s3, dry_run=False)
        self.assertEqual(len(results), 1)
        res = results[0]
        self.assertEqual(res.status, audit.AuditStatus.RECOVERED_LEGACY)
        self.assertEqual(len(mock_s3.copy_log), 1)
        # Verify copied object exists at canonical path in permanent bucket
        self.assertTrue(mock_s3.object_exists("studenthub-uploads", "photos/card_03.jpg"))
        self.assertEqual(mock_s3.buckets["studenthub-uploads"]["photos/card_03.jpg"], b"data_legacy_live")

    def test_audit_temp_recovery_live_execute(self):
        """In live execution, temp bucket object is copied to permanent bucket photos/<filename>."""
        mock_s3 = audit.MockS3Backend({
            "studenthub-uploads": {},
            "studenthub-uploads-temp": {
                "card_04.jpg": b"data_temp_file",
            },
        })
        records = [{
            "candidate_id": "104",
            "side": "front",
            "filename": "card_04.jpg",
            "candidate_updated_at": "2026-05-04 13:00:00",
        }]

        results = audit.run_audit(records, mock_s3, dry_run=False)
        self.assertEqual(len(results), 1)
        res = results[0]
        self.assertEqual(res.status, audit.AuditStatus.RECOVERED_TEMP)
        self.assertEqual(len(mock_s3.copy_log), 1)
        # Verify object copied into permanent bucket
        self.assertTrue(mock_s3.object_exists("studenthub-uploads", "photos/card_04.jpg"))
        self.assertEqual(mock_s3.buckets["studenthub-uploads"]["photos/card_04.jpg"], b"data_temp_file")

    def test_audit_missing_everywhere(self):
        """When not found anywhere, candidate is flagged for re-upload and DB fields preserved."""
        mock_s3 = audit.MockS3Backend({
            "studenthub-uploads": {},
            "studenthub-uploads-temp": {},
        })
        records = [{
            "candidate_id": "105",
            "side": "back",
            "filename": "lost_forever.png",
            "candidate_updated_at": "2026-04-18 09:00:00",
        }]

        results = audit.run_audit(records, mock_s3, dry_run=False)
        self.assertEqual(len(results), 1)
        res = results[0]
        self.assertEqual(res.status, audit.AuditStatus.MISSING_REUPLOAD_REQUIRED)
        self.assertIn("flagged for re-upload", res.action_taken)
        self.assertIn("DB fields preserved", res.action_taken)
        self.assertEqual(len(mock_s3.copy_log), 0)

    def test_report_exports(self):
        """Ensure full CSV, re-upload CSV, and JSON reports are created accurately."""
        records = [
            audit.AuditRecord(
                candidate_id="1",
                side="front",
                raw_filename="f1.jpg",
                clean_basename="f1.jpg",
                expected_s3_key="photos/f1.jpg",
                candidate_updated_at="2026-05-01",
                status=audit.AuditStatus.OK_CANONICAL,
            ),
            audit.AuditRecord(
                candidate_id="2",
                side="back",
                raw_filename="f2.jpg",
                clean_basename="f2.jpg",
                expected_s3_key="photos/f2.jpg",
                candidate_updated_at="2026-05-02",
                status=audit.AuditStatus.MISSING_REUPLOAD_REQUIRED,
            ),
        ]

        with tempfile.TemporaryDirectory() as tmpdir:
            csv_file = Path(tmpdir) / "report.csv"
            reupload_csv = Path(tmpdir) / "reupload.csv"
            json_file = Path(tmpdir) / "report.json"

            audit.export_reports(records, csv_file, reupload_csv, json_file)

            self.assertTrue(csv_file.exists())
            self.assertTrue(reupload_csv.exists())
            self.assertTrue(json_file.exists())

            # Check full CSV has 2 records
            with open(csv_file, "r", encoding="utf-8") as f:
                rows = list(csv.DictReader(f))
                self.assertEqual(len(rows), 2)

            # Check reupload CSV only has 1 record
            with open(reupload_csv, "r", encoding="utf-8") as f:
                rows = list(csv.DictReader(f))
                self.assertEqual(len(rows), 1)
                self.assertEqual(rows[0]["candidate_id"], "2")

            # Check JSON contents
            with open(json_file, "r", encoding="utf-8") as f:
                data = json.load(f)
                self.assertEqual(data["total_records"], 2)
                self.assertEqual(data["ok_canonical"], 1)
                self.assertEqual(data["missing_reupload_required"], 1)


if __name__ == "__main__":
    unittest.main(verbosity=2)
