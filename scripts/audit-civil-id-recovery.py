#!/usr/bin/env python3
"""
scripts/audit-civil-id-recovery.py

Phase 8 — Missing Civil ID Data Audit & Recovery Tool
For BAWES-Universe/studenthub (Issue #55)

Automates the audit, cross-bucket probing, and recovery for candidate Civil ID photos:
1. Queries or imports all candidate Civil ID photo references (front and back).
2. Checks canonical S3 path in permanent bucket: photos/<filename>
3. If missing, checks legacy path in permanent bucket: candidate-civil-id/<filename>
   - Copies legacy object to photos/<filename> if found.
4. If missing, checks temporary upload bucket: <filename> or photos/<filename>
   - Copies temporary object to permanent bucket photos/<filename> if found.
5. If missing everywhere, flags candidate for re-upload without mutating/clearing DB fields.
6. Outputs structured audit CSV, re-upload list CSV, JSON report, and terminal summary.
"""

from __future__ import annotations

import argparse
import csv
import datetime
import hashlib
import hmac
import json
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple


SQL_EXPORT_QUERY = """
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
""".strip()


class AuditStatus:
    OK_CANONICAL = "OK_CANONICAL"
    RECOVERED_LEGACY = "RECOVERED_LEGACY"
    RECOVERED_TEMP = "RECOVERED_TEMP"
    MISSING_REUPLOAD_REQUIRED = "MISSING_REUPLOAD_REQUIRED"
    ERROR = "ERROR"


@dataclass
class AuditRecord:
    candidate_id: str
    side: str
    raw_filename: str
    clean_basename: str
    expected_s3_key: str
    candidate_updated_at: str
    status: str = ""
    action_taken: str = ""
    source_location: str = ""
    target_location: str = ""
    error_message: str = ""


def normalize_civil_id_key(stored: Optional[str]) -> Tuple[str, str]:
    """
    Normalizes a stored Civil ID path to (clean_basename, canonical_s3_key).
    Mirrors Candidate::normalizeCivilIdPermanentS3Key PHP implementation:
    - Strips leading slashes and whitespace.
    - Strips 'photos/' or 'candidate-civil-id/' prefixes to extract raw basename.
    - Canonical S3 key is always photos/<clean_basename>.
    """
    if not stored:
        return "", ""
    v = str(stored).strip().lstrip("/")
    if not v:
        return "", ""

    # If it begins with candidate-civil-id/
    if v.startswith("candidate-civil-id/"):
        rest = v[len("candidate-civil-id/"):]
        basename = Path(rest).name
    elif v.startswith("photos/"):
        rest = v[len("photos/"):]
        basename = Path(rest).name
    else:
        basename = Path(v).name

    if not basename:
        return "", ""

    canonical_key = f"photos/{basename}"
    return basename, canonical_key


class S3Backend:
    """Interface for S3 operations."""

    def object_exists(self, bucket: str, key: str) -> bool:
        raise NotImplementedError

    def copy_object(
        self,
        source_bucket: str,
        source_key: str,
        target_bucket: str,
        target_key: str,
    ) -> bool:
        raise NotImplementedError


class MockS3Backend(S3Backend):
    """In-memory mock S3 backend for offline tests and validation."""

    def __init__(self, initial_buckets: Optional[Dict[str, Dict[str, bytes]]] = None):
        # buckets: {bucket_name: {key: content}}
        self.buckets: Dict[str, Dict[str, bytes]] = initial_buckets or {}
        self.copy_log: List[Dict[str, str]] = []

    def object_exists(self, bucket: str, key: str) -> bool:
        return key in self.buckets.get(bucket, {})

    def copy_object(
        self,
        source_bucket: str,
        source_key: str,
        target_bucket: str,
        target_key: str,
    ) -> bool:
        if not self.object_exists(source_bucket, source_key):
            return False
        content = self.buckets[source_bucket][source_key]
        if target_bucket not in self.buckets:
            self.buckets[target_bucket] = {}
        self.buckets[target_bucket][target_key] = content
        self.copy_log.append({
            "source_bucket": source_bucket,
            "source_key": source_key,
            "target_bucket": target_bucket,
            "target_key": target_key,
        })
        return True


class Boto3S3Backend(S3Backend):
    """Production S3 backend using boto3."""

    def __init__(self, endpoint_url: Optional[str] = None, region_name: Optional[str] = None):
        import boto3
        from botocore.config import Config

        cfg = Config(signature_version="s3v4", retries={"max_attempts": 3, "mode": "standard"})
        self.client = boto3.client(
            "s3",
            endpoint_url=endpoint_url,
            region_name=region_name or os.getenv("AWS_REGION", "us-east-1"),
            config=cfg,
        )

    def object_exists(self, bucket: str, key: str) -> bool:
        from botocore.exceptions import ClientError
        try:
            self.client.head_object(Bucket=bucket, Key=key)
            return True
        except ClientError as e:
            code = e.response.get("Error", {}).get("Code", "")
            if code in ("404", "NoSuchKey", "NotFound"):
                return False
            raise

    def copy_object(
        self,
        source_bucket: str,
        source_key: str,
        target_bucket: str,
        target_key: str,
    ) -> bool:
        copy_source = {"Bucket": source_bucket, "Key": source_key}
        self.client.copy_object(
            CopySource=copy_source,
            Bucket=target_bucket,
            Key=target_key,
        )
        return True


class SigV4S3Backend(S3Backend):
    """
    Standard-library HTTP S3 backend implementing AWS Signature Version 4.
    Works with zero external dependencies when boto3 is not installed.
    """

    def __init__(
        self,
        access_key: str,
        secret_key: str,
        region: str = "us-east-1",
        session_token: Optional[str] = None,
        endpoint_url: Optional[str] = None,
    ):
        self.access_key = access_key
        self.secret_key = secret_key
        self.region = region
        self.session_token = session_token
        self.endpoint_url = endpoint_url

    def _sign(self, key: bytes, msg: str) -> bytes:
        return hmac.new(key, msg.encode("utf-8"), hashlib.sha256).digest()

    def _get_signature_key(self, date_stamp: str) -> bytes:
        k_date = self._sign(("AWS4" + self.secret_key).encode("utf-8"), date_stamp)
        k_region = self._sign(k_date, self.region)
        k_service = self._sign(k_region, "s3")
        return self._sign(k_service, "aws4_request")

    def _send_request(
        self,
        method: str,
        bucket: str,
        key: str,
        extra_headers: Optional[Dict[str, str]] = None,
    ) -> Tuple[int, Dict[str, str], bytes]:
        now = datetime.datetime.now(datetime.timezone.utc)
        amz_date = now.strftime("%Y%m%dT%H%M%SZ")
        date_stamp = now.strftime("%Y%m%d")

        if self.endpoint_url:
            base = self.endpoint_url.rstrip("/")
            url = f"{base}/{bucket}/{urllib.parse.quote(key)}"
            host = urllib.parse.urlsplit(base).netloc
        else:
            host = f"{bucket}.s3.{self.region}.amazonaws.com"
            url = f"https://{host}/{urllib.parse.quote(key)}"

        canonical_uri = f"/{urllib.parse.quote(key)}" if not self.endpoint_url else f"/{bucket}/{urllib.parse.quote(key)}"
        canonical_querystring = ""
        payload_hash = hashlib.sha256(b"").hexdigest()

        headers = {
            "host": host,
            "x-amz-date": amz_date,
            "x-amz-content-sha256": payload_hash,
        }
        if self.session_token:
            headers["x-amz-security-token"] = self.session_token
        if extra_headers:
            for k, v in extra_headers.items():
                headers[k.lower()] = v

        signed_headers = ";".join(sorted(headers.keys()))
        canonical_headers = "".join(f"{k}:{headers[k]}\n" for k in sorted(headers.keys()))

        canonical_request = (
            f"{method}\n{canonical_uri}\n{canonical_querystring}\n"
            f"{canonical_headers}\n{signed_headers}\n{payload_hash}"
        )

        algorithm = "AWS4-HMAC-SHA256"
        credential_scope = f"{date_stamp}/{self.region}/s3/aws4_request"
        string_to_sign = (
            f"{algorithm}\n{amz_date}\n{credential_scope}\n"
            f"{hashlib.sha256(canonical_request.encode('utf-8')).hexdigest()}"
        )

        signing_key = self._get_signature_key(date_stamp)
        signature = hmac.new(signing_key, string_to_sign.encode("utf-8"), hashlib.sha256).hexdigest()

        auth_header = (
            f"{algorithm} Credential={self.access_key}/{credential_scope}, "
            f"SignedHeaders={signed_headers}, Signature={signature}"
        )

        req_headers = {
            "Host": host,
            "x-amz-date": amz_date,
            "x-amz-content-sha256": payload_hash,
            "Authorization": auth_header,
        }
        if self.session_token:
            req_headers["x-amz-security-token"] = self.session_token
        if extra_headers:
            req_headers.update(extra_headers)

        req = urllib.request.Request(url, headers=req_headers, method=method)
        try:
            with urllib.request.urlopen(req) as resp:
                return resp.status, dict(resp.headers), resp.read()
        except urllib.error.HTTPError as e:
            return e.code, dict(e.headers), e.read()

    def object_exists(self, bucket: str, key: str) -> bool:
        status, _, _ = self._send_request("HEAD", bucket, key)
        return status == 200

    def copy_object(
        self,
        source_bucket: str,
        source_key: str,
        target_bucket: str,
        target_key: str,
    ) -> bool:
        copy_source = urllib.parse.quote(f"{source_bucket}/{source_key}")
        status, _, _ = self._send_request(
            "PUT",
            target_bucket,
            target_key,
            extra_headers={"x-amz-copy-source": copy_source},
        )
        return status in (200, 204)


def create_s3_backend(
    dry_run: bool = False,
    endpoint_url: Optional[str] = None,
    mock_backend: Optional[MockS3Backend] = None,
) -> S3Backend:
    if mock_backend:
        return mock_backend

    try:
        import boto3
        return Boto3S3Backend(endpoint_url=endpoint_url)
    except ImportError:
        ak = os.getenv("AWS_ACCESS_KEY_ID") or os.getenv("AWS_S3_PERM_ACCESS_KEY_ID")
        sk = os.getenv("AWS_SECRET_ACCESS_KEY") or os.getenv("AWS_S3_PERM_SECRET_ACCESS_KEY")
        token = os.getenv("AWS_SESSION_TOKEN")
        region = os.getenv("AWS_REGION", "us-east-1")
        if ak and sk:
            return SigV4S3Backend(
                access_key=ak,
                secret_key=sk,
                region=region,
                session_token=token,
                endpoint_url=endpoint_url,
            )
        if dry_run:
            # Safe offline mock backend when no credentials are provided during a dry-run
            return MockS3Backend()
        raise RuntimeError(
            "Neither boto3 nor valid AWS credentials (AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY) "
            "are available for live execution."
        )


def run_audit(
    records: List[Dict[str, str]],
    s3: S3Backend,
    perm_bucket: str = "studenthub-uploads",
    temp_bucket: str = "studenthub-uploads-temp",
    dry_run: bool = True,
) -> List[AuditRecord]:
    results: List[AuditRecord] = []

    for item in records:
        cid = str(item.get("candidate_id", "")).strip()
        side = str(item.get("side", "")).strip()
        raw_fn = str(item.get("filename", "")).strip()
        updated_at = str(item.get("candidate_updated_at", "")).strip()

        basename, canonical_key = normalize_civil_id_key(raw_fn)

        record = AuditRecord(
            candidate_id=cid,
            side=side,
            raw_filename=raw_fn,
            clean_basename=basename,
            expected_s3_key=canonical_key,
            candidate_updated_at=updated_at,
        )

        if not basename or not canonical_key:
            record.status = AuditStatus.ERROR
            record.action_taken = "Empty or malformed filename"
            results.append(record)
            continue

        legacy_key = f"candidate-civil-id/{basename}"

        try:
            # Step 1: Probe canonical location in permanent bucket
            if s3.object_exists(perm_bucket, canonical_key):
                record.status = AuditStatus.OK_CANONICAL
                record.action_taken = "Present at canonical path; no action required"
                record.source_location = f"s3://{perm_bucket}/{canonical_key}"
                record.target_location = f"s3://{perm_bucket}/{canonical_key}"
                results.append(record)
                continue

            # Step 2: Probe legacy location in permanent bucket
            if s3.object_exists(perm_bucket, legacy_key):
                record.source_location = f"s3://{perm_bucket}/{legacy_key}"
                record.target_location = f"s3://{perm_bucket}/{canonical_key}"
                if dry_run:
                    record.status = AuditStatus.RECOVERED_LEGACY
                    record.action_taken = f"[DRY-RUN] Would copy from {legacy_key} to {canonical_key}"
                else:
                    success = s3.copy_object(
                        source_bucket=perm_bucket,
                        source_key=legacy_key,
                        target_bucket=perm_bucket,
                        target_key=canonical_key,
                    )
                    if success:
                        record.status = AuditStatus.RECOVERED_LEGACY
                        record.action_taken = f"Recovered: copied from {legacy_key} to {canonical_key}"
                    else:
                        record.status = AuditStatus.ERROR
                        record.action_taken = f"Failed copying legacy key {legacy_key}"
                results.append(record)
                continue

            # Step 3: Probe temporary upload bucket
            temp_probes = [basename, canonical_key]
            found_temp_key = None
            for probe_key in temp_probes:
                if s3.object_exists(temp_bucket, probe_key):
                    found_temp_key = probe_key
                    break

            if found_temp_key:
                record.source_location = f"s3://{temp_bucket}/{found_temp_key}"
                record.target_location = f"s3://{perm_bucket}/{canonical_key}"
                if dry_run:
                    record.status = AuditStatus.RECOVERED_TEMP
                    record.action_taken = (
                        f"[DRY-RUN] Would copy from temp bucket {temp_bucket}/{found_temp_key} "
                        f"to permanent {perm_bucket}/{canonical_key}"
                    )
                else:
                    success = s3.copy_object(
                        source_bucket=temp_bucket,
                        source_key=found_temp_key,
                        target_bucket=perm_bucket,
                        target_key=canonical_key,
                    )
                    if success:
                        record.status = AuditStatus.RECOVERED_TEMP
                        record.action_taken = (
                            f"Recovered: copied from temp {temp_bucket}/{found_temp_key} "
                            f"to permanent {perm_bucket}/{canonical_key}"
                        )
                    else:
                        record.status = AuditStatus.ERROR
                        record.action_taken = f"Failed copying from temp key {found_temp_key}"
                results.append(record)
                continue

            # Step 4: Missing from all locations -> Flag candidate for re-upload
            record.status = AuditStatus.MISSING_REUPLOAD_REQUIRED
            record.action_taken = (
                "Missing across canonical, legacy, and temp storage; "
                "flagged for re-upload (DB fields preserved)"
            )
            results.append(record)

        except Exception as e:
            record.status = AuditStatus.ERROR
            record.error_message = str(e)
            record.action_taken = f"Error during S3 probe: {e}"
            results.append(record)

    return results


def export_reports(
    results: List[AuditRecord],
    csv_path: Path,
    reupload_csv_path: Path,
    json_path: Path,
) -> None:
    csv_path.parent.mkdir(parents=True, exist_ok=True)
    reupload_csv_path.parent.mkdir(parents=True, exist_ok=True)
    json_path.parent.mkdir(parents=True, exist_ok=True)

    fieldnames = [
        "candidate_id",
        "side",
        "raw_filename",
        "clean_basename",
        "expected_s3_key",
        "candidate_updated_at",
        "status",
        "action_taken",
        "source_location",
        "target_location",
        "error_message",
    ]

    # Full CSV report
    with open(csv_path, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for r in results:
            writer.writerow(asdict(r))

    # Missing / Re-upload required CSV
    reupload_candidates = [
        r for r in results if r.status == AuditStatus.MISSING_REUPLOAD_REQUIRED
    ]
    with open(reupload_csv_path, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for r in reupload_candidates:
            writer.writerow(asdict(r))

    # JSON report with metadata
    summary = {
        "timestamp": datetime.datetime.now(datetime.timezone.utc).isoformat(),
        "total_records": len(results),
        "ok_canonical": sum(1 for r in results if r.status == AuditStatus.OK_CANONICAL),
        "recovered_legacy": sum(1 for r in results if r.status == AuditStatus.RECOVERED_LEGACY),
        "recovered_temp": sum(1 for r in results if r.status == AuditStatus.RECOVERED_TEMP),
        "missing_reupload_required": len(reupload_candidates),
        "errors": sum(1 for r in results if r.status == AuditStatus.ERROR),
        "records": [asdict(r) for r in results],
    }

    with open(json_path, "w", encoding="utf-8") as f:
        json.dump(summary, f, indent=2, ensure_ascii=False)


def print_summary(results: List[AuditRecord], dry_run: bool) -> None:
    total = len(results)
    ok_count = sum(1 for r in results if r.status == AuditStatus.OK_CANONICAL)
    legacy_count = sum(1 for r in results if r.status == AuditStatus.RECOVERED_LEGACY)
    temp_count = sum(1 for r in results if r.status == AuditStatus.RECOVERED_TEMP)
    missing_count = sum(1 for r in results if r.status == AuditStatus.MISSING_REUPLOAD_REQUIRED)
    error_count = sum(1 for r in results if r.status == AuditStatus.ERROR)

    mode_str = "DRY-RUN (Safe Simulation — No S3 mutations)" if dry_run else "EXECUTE (Live remediation active)"

    print("\n" + "=" * 70)
    print(f"CIVIL ID S3 AUDIT & RECOVERY SUMMARY [{mode_str}]")
    print("=" * 70)
    print(f"  Total Evaluated Records     : {total:>6}")
    print(f"  OK (Canonical S3 Exists)    : {ok_count:>6} ({(ok_count/total*100) if total else 0:>5.1f}%)")
    print(f"  Recovered from Legacy Prefix: {legacy_count:>6} ({(legacy_count/total*100) if total else 0:>5.1f}%)")
    print(f"  Recovered from Temp Bucket  : {temp_count:>6} ({(temp_count/total*100) if total else 0:>5.1f}%)")
    print(f"  Missing (Re-upload Flagged) : {missing_count:>6} ({(missing_count/total*100) if total else 0:>5.1f}%)")
    if error_count:
        print(f"  Errors Encountered          : {error_count:>6} ({(error_count/total*100) if total else 0:>5.1f}%)")
    print("=" * 70)
    if missing_count > 0:
        print(f"NOTICE: {missing_count} records require candidate re-upload.")
        print("        Per Phase 8 rules, database fields remain preserved (NOT cleared).")
    print("=" * 70 + "\n")


def parse_args(argv: Optional[List[str]] = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Phase 8: Missing Civil ID Data Audit & Recovery Tool"
    )
    parser.add_argument(
        "--input-csv",
        type=Path,
        help="Path to CSV file exported from candidate Civil ID query",
    )
    parser.add_argument(
        "--input-json",
        type=Path,
        help="Path to JSON file containing candidate records",
    )
    parser.add_argument(
        "--perm-bucket",
        default=os.getenv("AWS_S3_PERM_BUCKET", "studenthub-uploads"),
        help="Permanent S3 bucket name (default: studenthub-uploads)",
    )
    parser.add_argument(
        "--temp-bucket",
        default=os.getenv("AWS_S3_TEMP_BUCKET", "studenthub-uploads-temp"),
        help="Temporary upload S3 bucket name (default: studenthub-uploads-temp)",
    )
    parser.add_argument(
        "--endpoint-url",
        default=os.getenv("AWS_S3_ENDPOINT_URL"),
        help="Custom S3 endpoint URL (e.g. MinIO or LocalStack)",
    )
    parser.add_argument(
        "--execute",
        action="store_true",
        help="Execute S3 copy operations (default is dry-run mode)",
    )
    parser.add_argument(
        "--output-csv",
        type=Path,
        default=Path("console/runtime/civil_id_audit_report.csv"),
        help="Output path for full audit CSV",
    )
    parser.add_argument(
        "--output-reupload",
        type=Path,
        default=Path("console/runtime/candidates_requiring_reupload.csv"),
        help="Output path for candidates requiring re-upload CSV",
    )
    parser.add_argument(
        "--output-json",
        type=Path,
        default=Path("console/runtime/civil_id_audit_report.json"),
        help="Output path for full JSON report",
    )
    parser.add_argument(
        "--print-sql",
        action="store_true",
        help="Print the Phase 8 SQL query and exit",
    )
    return parser.parse_args(argv)


def load_input_records(args: argparse.Namespace) -> List[Dict[str, str]]:
    if args.input_csv and args.input_csv.exists():
        with open(args.input_csv, "r", encoding="utf-8") as f:
            reader = csv.DictReader(f)
            return list(reader)
    if args.input_json and args.input_json.exists():
        with open(args.input_json, "r", encoding="utf-8") as f:
            data = json.load(f)
            if isinstance(data, list):
                return data
            if isinstance(data, dict) and "records" in data:
                return data["records"]
    return []


def main(argv: Optional[List[str]] = None) -> int:
    args = parse_args(argv)

    if args.print_sql:
        print(SQL_EXPORT_QUERY)
        return 0

    dry_run = not args.execute

    records = load_input_records(args)
    if not records:
        print(
            "No input records found. Please provide --input-csv or --input-json, "
            "or use --print-sql to view the database query.",
            file=sys.stderr,
        )
        return 1

    s3 = create_s3_backend(dry_run=dry_run, endpoint_url=args.endpoint_url)
    results = run_audit(
        records=records,
        s3=s3,
        perm_bucket=args.perm_bucket,
        temp_bucket=args.temp_bucket,
        dry_run=dry_run,
    )

    export_reports(
        results=results,
        csv_path=args.output_csv,
        reupload_csv_path=args.output_reupload,
        json_path=args.output_json,
    )

    print_summary(results=results, dry_run=dry_run)
    print(f"Reports exported:")
    print(f"  Full CSV       : {args.output_csv}")
    print(f"  Re-upload CSV  : {args.output_reupload}")
    print(f"  Full JSON      : {args.output_json}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
