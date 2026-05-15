#!/usr/bin/env python3
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
controller = ROOT / "candidate/modules/v1/controllers/AccountController.php"
source = controller.read_text()

required_snippets = [
    "isMediaConvertWebhookEvent($data, $detail, $jobId)",
    "$data->source !== 'aws.mediaconvert'",
    "$data->{'detail-type'} !== 'MediaConvert Job State Change'",
    "(string) $metadataUser !== (string) $model->candidate_id",
    "$status = strtoupper((string) $detail->status)",
    "$status != 'COMPLETE'",
    "getMediaConvertOutputFilePath($detail)",
]

missing = [snippet for snippet in required_snippets if snippet not in source]

if missing:
    raise SystemExit(
        "Missing MediaConvert webhook hardening checks:\n- "
        + "\n- ".join(missing)
    )

print("MediaConvert webhook hardening checks are present.")
